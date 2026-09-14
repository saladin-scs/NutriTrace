<?php

namespace App\Http\Controllers;

use App\Actions\Shipments\CreateShipmentAction;
use App\Actions\Shipments\DispatchShipmentAction;
use App\Actions\Shipments\ReceiveShipmentAction;
use App\Domain\Distribution\ControlTowerQuery;
use App\Http\Requests\Shipments\StoreShipmentRequest;
use App\Models\Batch;
use App\Models\DistributionChannel;
use App\Models\DistributionNode;
use App\Models\Organization;
use App\Models\Route as LogisticsRoute;
use App\Models\Shipment;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Shipment::class);

        $shipments = Shipment::query()
            ->with(['vehicle', 'fromOrganization', 'toOrganization', 'items.batch'])
            ->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('shipments.index', compact('shipments'));
    }

    public function create(): View
    {
        $this->authorize('create', Shipment::class);

        return view('shipments.create', [
            'batches' => Batch::query()->with(['product', 'organization'])->latest()->limit(100)->get(),
            'vehicles' => Vehicle::query()->orderBy('registration')->get(),
            'routes' => LogisticsRoute::query()->with(['originNode', 'destinationNode'])->orderBy('code')->get(),
            'channels' => DistributionChannel::query()->orderBy('name')->get(),
            'organizations' => Organization::query()->orderBy('name')->get(['id', 'name', 'type']),
            'nodes' => DistributionNode::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'code', 'organization_id', 'node_type']),
        ]);
    }

    public function store(StoreShipmentRequest $request, CreateShipmentAction $action): RedirectResponse
    {
        $this->authorize('create', Shipment::class);

        $data = $request->validated();
        $items = $data['items'];
        unset($data['items']);

        $shipment = $action->execute($request->user(), $data, $items);

        return redirect()
            ->route('shipments.show', $shipment)
            ->with('success', 'Shipment créé : '.$shipment->code);
    }

    public function show(Shipment $shipment, ControlTowerQuery $query): View
    {
        $this->authorize('view', $shipment);

        $shipment->load([
            'items.batch.product',
            'vehicle',
            'route.originNode',
            'route.destinationNode',
            'fromOrganization',
            'toOrganization',
            'originNode',
            'destinationNode',
            'channel',
            'positions',
        ]);

        return view('shipments.show', [
            'shipment' => $shipment,
            'panel' => $query->serializeShipment($shipment),
        ]);
    }

    public function dispatchShipment(
        Request $request,
        Shipment $shipment,
        DispatchShipmentAction $action,
    ): RedirectResponse {
        $this->authorize('dispatch', $shipment);

        $action->execute($request->user(), $shipment, $request->validate([
            'delayed' => ['nullable', 'boolean'],
            'eta_at' => ['nullable', 'date'],
            'current_temperature_c' => ['nullable', 'numeric'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
        ]));

        return back()->with('success', 'Shipment expédié.');
    }

    public function receive(
        Request $request,
        Shipment $shipment,
        ReceiveShipmentAction $action,
    ): RedirectResponse {
        $this->authorize('receive', $shipment);

        $action->execute($request->user(), $shipment, $request->validate([
            'current_temperature_c' => ['nullable', 'numeric'],
        ]));

        return back()->with('success', 'Shipment réceptionné.');
    }
}
