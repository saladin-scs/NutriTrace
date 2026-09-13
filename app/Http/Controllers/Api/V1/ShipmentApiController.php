<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Shipments\CreateShipmentAction;
use App\Actions\Shipments\DispatchShipmentAction;
use App\Actions\Shipments\ReceiveShipmentAction;
use App\Domain\Distribution\ControlTowerQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shipments\StoreShipmentRequest;
use App\Models\Shipment;
use App\Models\VehiclePosition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipmentApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Shipment::class);

        $shipments = Shipment::query()
            ->with(['vehicle', 'fromOrganization', 'toOrganization', 'items.batch.product', 'route', 'originNode', 'destinationNode'])
            ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20);

        $query = app(ControlTowerQuery::class);

        return response()->json([
            'data' => $shipments->getCollection()->map(fn (Shipment $s) => $query->serializeShipment($s))->values(),
            'meta' => [
                'current_page' => $shipments->currentPage(),
                'last_page' => $shipments->lastPage(),
                'total' => $shipments->total(),
            ],
        ]);
    }

    public function store(StoreShipmentRequest $request, CreateShipmentAction $action): JsonResponse
    {
        $this->authorize('create', Shipment::class);

        $data = $request->validated();
        $items = $data['items'];
        unset($data['items']);

        $shipment = $action->execute($request->user(), $data, $items);

        return response()->json([
            'data' => app(ControlTowerQuery::class)->serializeShipment($shipment),
        ], 201);
    }

    public function show(Shipment $shipment, ControlTowerQuery $query): JsonResponse
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
        ]);

        return response()->json(['data' => $query->serializeShipment($shipment)]);
    }

    public function dispatch(
        Request $request,
        Shipment $shipment,
        DispatchShipmentAction $action,
        ControlTowerQuery $query,
    ): JsonResponse {
        $this->authorize('dispatch', $shipment);

        $shipment = $action->execute($request->user(), $shipment, $request->validate([
            'delayed' => ['nullable', 'boolean'],
            'eta_at' => ['nullable', 'date'],
            'current_temperature_c' => ['nullable', 'numeric'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
        ]));

        return response()->json(['data' => $query->serializeShipment($shipment)]);
    }

    public function receive(
        Request $request,
        Shipment $shipment,
        ReceiveShipmentAction $action,
        ControlTowerQuery $query,
    ): JsonResponse {
        $this->authorize('receive', $shipment);

        $shipment = $action->execute($request->user(), $shipment, $request->validate([
            'current_temperature_c' => ['nullable', 'numeric'],
        ]));

        return response()->json(['data' => $query->serializeShipment($shipment)]);
    }

    public function tracking(Shipment $shipment): JsonResponse
    {
        $this->authorize('view', $shipment);

        $positions = VehiclePosition::query()
            ->where('shipment_id', $shipment->id)
            ->orderBy('recorded_at')
            ->get()
            ->map(fn (VehiclePosition $p) => [
                'lat' => (float) $p->latitude,
                'lng' => (float) $p->longitude,
                'speed_kmh' => $p->speed_kmh,
                'heading' => $p->heading,
                'temperature_c' => $p->temperature_c,
                'recorded_at' => $p->recorded_at?->toIso8601String(),
            ]);

        return response()->json([
            'data' => [
                'shipment_id' => $shipment->id,
                'code' => $shipment->code,
                'status' => $shipment->status->value,
                'positions' => $positions,
            ],
        ]);
    }
}
