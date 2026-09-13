<?php

namespace App\Http\Controllers;

use App\Actions\Batches\CreateBatchAction;
use App\Actions\Batches\RecordDistributionAction;
use App\Actions\Batches\RecordTransformationAction;
use App\Domain\Product\QrPassportUrl;
use App\Domain\Traceability\TraceabilityChainBuilder;
use App\Enums\TransportMode;
use App\Http\Requests\Batches\RecordDistributionRequest;
use App\Http\Requests\Batches\RecordTransformationRequest;
use App\Http\Requests\Batches\StoreBatchRequest;
use App\Models\Batch;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BatchController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Batch::class);

        $batches = Batch::query()
            ->with(['product', 'organization'])
            ->when(! $request->user()->isAdmin(), function ($query) use ($request) {
                $query->whereIn('organization_id', $request->user()->organizations()->pluck('organizations.id'));
            })
            ->when($request->string('q')->toString(), function ($query, string $q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('code', 'like', "%{$q}%")
                        ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$q}%"));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('batches.index', compact('batches'));
    }

    public function create(Request $request, Product $product): View
    {
        $this->authorize('view', $product);
        $this->authorize('create', Batch::class);

        return view('batches.create', [
            'product' => $product->load('organization.locations'),
        ]);
    }

    public function store(
        StoreBatchRequest $request,
        Product $product,
        CreateBatchAction $action,
    ): RedirectResponse {
        $this->authorize('view', $product);
        $this->authorize('create', Batch::class);

        $batch = $action->execute($request->user(), $product, $request->validated());

        return redirect()
            ->route('batches.show', $batch)
            ->with('success', 'Lot créé : '.$batch->code);
    }

    public function show(
        Batch $batch,
        TraceabilityChainBuilder $chain,
        QrPassportUrl $qr,
    ): View {
        $this->authorize('view', $batch);

        $batch->load([
            'product.category',
            'organization',
            'productionLocation',
            'traceabilityEvents.organization',
            'traceabilityEvents.location',
            'parentBatch',
            'distributions.fromOrganization',
            'distributions.toOrganization',
        ]);

        return view('batches.show', [
            'batch' => $batch,
            'nodes' => $chain->build($batch),
            'passportUrl' => $qr->forCode($batch->code),
            'qrImageUrl' => $qr->qrImageUrl($batch->code),
            'organizations' => $this->memberOrganizations(request()),
            'targets' => Organization::query()->orderBy('name')->get(),
            'transportModes' => TransportMode::cases(),
        ]);
    }

    public function transform(
        RecordTransformationRequest $request,
        Batch $batch,
        RecordTransformationAction $action,
    ): RedirectResponse {
        $this->authorize('transform', $batch);

        $org = Organization::query()->findOrFail($request->validated('organization_id'));
        abort_unless(
            $request->user()->isAdmin() || $request->user()->belongsToOrganization($org),
            403
        );

        $transformation = $action->execute($request->user(), $batch, $org, $request->validated());

        return redirect()
            ->route('batches.show', $transformation->output_batch_id)
            ->with('success', 'Transformation enregistrée. Nouveau lot généré.');
    }

    public function distribute(
        RecordDistributionRequest $request,
        Batch $batch,
        RecordDistributionAction $action,
    ): RedirectResponse {
        $this->authorize('distribute', $batch);

        $to = Organization::query()->findOrFail($request->validated('to_organization_id'));

        $action->execute(
            $request->user(),
            $batch,
            $batch->organization,
            $to,
            $request->validated(),
        );

        return back()->with('success', 'Distribution enregistrée.');
    }

    /**
     * @return \Illuminate\Support\Collection<int, Organization>
     */
    private function memberOrganizations(Request $request)
    {
        if ($request->user()->isAdmin()) {
            return Organization::query()->orderBy('name')->get();
        }

        return $request->user()->organizations()->orderBy('name')->get();
    }
}
