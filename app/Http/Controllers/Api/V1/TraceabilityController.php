<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Batches\CreateBatchAction;
use App\Actions\Batches\RecordDistributionAction;
use App\Actions\Batches\RecordTransformationAction;
use App\Actions\Products\CreateProductAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Batches\RecordDistributionRequest;
use App\Http\Requests\Batches\RecordTransformationRequest;
use App\Http\Requests\Batches\StoreBatchRequest;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Resources\Api\V1\BatchResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Batch;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TraceabilityController extends Controller
{
    public function products(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::query()
            ->with(['organization', 'category'])
            ->withCount('batches')
            ->when(! $request->user()->isAdmin(), function ($query) use ($request) {
                $query->whereIn('organization_id', $request->user()->organizations()->pluck('organizations.id'));
            })
            ->latest()
            ->paginate(20);

        return ProductResource::collection($products);
    }

    public function storeProduct(StoreProductRequest $request, CreateProductAction $action): JsonResponse
    {
        $this->authorize('create', Product::class);
        $organization = Organization::query()->findOrFail($request->validated('organization_id'));
        abort_unless($request->user()->isAdmin() || $request->user()->belongsToOrganization($organization), 403);

        $product = $action->execute($request->user(), $organization, $request->validated());

        return response()->json(['data' => ProductResource::make($product)->resolve()], 201);
    }

    public function showProduct(Product $product): ProductResource
    {
        $this->authorize('view', $product);

        return ProductResource::make($product->load(['organization', 'category'])->loadCount('batches'));
    }

    public function batches(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Batch::class);

        $batches = Batch::query()
            ->with(['product', 'organization', 'traceabilityEvents.organization', 'traceabilityEvents.location'])
            ->when(! $request->user()->isAdmin(), function ($query) use ($request) {
                $query->whereIn('organization_id', $request->user()->organizations()->pluck('organizations.id'));
            })
            ->latest()
            ->paginate(20);

        return BatchResource::collection($batches);
    }

    public function storeBatch(StoreBatchRequest $request, Product $product, CreateBatchAction $action): JsonResponse
    {
        $this->authorize('view', $product);
        $this->authorize('create', Batch::class);

        $batch = $action->execute($request->user(), $product, $request->validated());

        return response()->json([
            'data' => BatchResource::make($batch->load(['product', 'organization', 'traceabilityEvents.organization', 'traceabilityEvents.location']))->resolve(),
        ], 201);
    }

    public function showBatch(Batch $batch): BatchResource
    {
        $this->authorize('view', $batch);

        return BatchResource::make($batch->load([
            'product', 'organization', 'traceabilityEvents.organization', 'traceabilityEvents.location',
        ]));
    }

    public function traceability(string $code): JsonResponse
    {
        $batch = Batch::query()
            ->with([
                'product.category', 'product.organization', 'organization',
                'traceabilityEvents.organization', 'traceabilityEvents.location',
                'environmentalMetrics', 'certifications',
            ])
            ->where('code', $code)
            ->firstOrFail();

        return response()->json([
            'data' => BatchResource::make($batch)->resolve(),
        ]);
    }

    public function transform(RecordTransformationRequest $request, Batch $batch, RecordTransformationAction $action): JsonResponse
    {
        $this->authorize('transform', $batch);
        $org = Organization::query()->findOrFail($request->validated('organization_id'));
        abort_unless($request->user()->isAdmin() || $request->user()->belongsToOrganization($org), 403);

        $transformation = $action->execute($request->user(), $batch, $org, $request->validated());

        return response()->json([
            'data' => BatchResource::make(
                $transformation->outputBatch->load(['product', 'organization', 'traceabilityEvents.organization', 'traceabilityEvents.location'])
            )->resolve(),
        ], 201);
    }

    public function distribute(RecordDistributionRequest $request, Batch $batch, RecordDistributionAction $action): JsonResponse
    {
        $this->authorize('distribute', $batch);
        $to = Organization::query()->findOrFail($request->validated('to_organization_id'));
        $distribution = $action->execute($request->user(), $batch, $batch->organization, $to, $request->validated());

        return response()->json([
            'message' => 'Distribution enregistrée.',
            'distribution_id' => $distribution->id,
            'data' => BatchResource::make($batch->fresh()->load([
                'product', 'organization', 'traceabilityEvents.organization', 'traceabilityEvents.location',
            ]))->resolve(),
        ]);
    }
}
