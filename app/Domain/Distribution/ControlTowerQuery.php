<?php

namespace App\Domain\Distribution;

use App\Enums\ShipmentStatus;
use App\Models\ColdRoom;
use App\Models\DistributionNode;
use App\Models\Shipment;
use App\Models\Vehicle;
use App\Models\VehiclePosition;
use Illuminate\Support\Carbon;

final class ControlTowerQuery
{
    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $activeStatuses = [
            ShipmentStatus::Dispatched->value,
            ShipmentStatus::InTransit->value,
            ShipmentStatus::Arrived->value,
            ShipmentStatus::Delayed->value,
        ];

        $activeShipments = Shipment::query()->whereIn('status', $activeStatuses)->count();
        $deliveriesToday = Shipment::query()
            ->where('status', ShipmentStatus::Delivered)
            ->whereDate('delivered_at', Carbon::today())
            ->count();
        $delayed = Shipment::query()->where('status', ShipmentStatus::Delayed)->count();
        $coldRoomsActive = ColdRoom::query()->where('status', 'active')->count();
        $avgOccupancy = (float) (ColdRoom::query()->where('status', 'active')->get()->avg(fn ($r) => $r->occupancyRate()) ?? 0);
        $tempAlerts = \App\Models\TemperatureRecord::query()
            ->whereIn('status', ['warning', 'critical'])
            ->where('recorded_at', '>=', now()->subDay())
            ->count();

        $openAnomalies = \App\Models\Anomaly::query()->active()->count();
        $criticalAnomalies = \App\Models\Anomaly::query()->active()->where('severity', 'critical')->count();

        return [
            'active_shipments' => $activeShipments,
            'deliveries_today' => $deliveriesToday,
            'delayed_shipments' => $delayed,
            'cold_rooms_active' => $coldRoomsActive,
            'cold_chain_avg_occupancy_pct' => round($avgOccupancy, 1),
            'temperature_alerts_24h' => $tempAlerts,
            'open_anomalies' => $openAnomalies,
            'critical_anomalies' => $criticalAnomalies,
            'alerts_count' => $openAnomalies > 0 ? $openAnomalies : ($delayed + $tempAlerts),
            'vehicles_in_transit' => Vehicle::query()->where('status', 'in_transit')->count(),
        ];
    }

    /**
     * Payload for the Control Tower map and live panels.
     *
     * @return array<string, mixed>
     */
    public function mapPayload(): array
    {
        $nodes = DistributionNode::query()
            ->with(['location', 'organization', 'channel'])
            ->where('status', 'active')
            ->get()
            ->map(function (DistributionNode $node) {
                $lat = $node->mapLatitude();
                $lng = $node->mapLongitude();
                if ($lat === null || $lng === null) {
                    return null;
                }

                return [
                    'id' => $node->id,
                    'code' => $node->code,
                    'name' => $node->name,
                    'type' => $node->node_type->value,
                    'type_label' => $node->node_type->label(),
                    'channel' => $node->channel?->name,
                    'organization' => $node->organization?->name,
                    'lat' => $lat,
                    'lng' => $lng,
                ];
            })
            ->filter()
            ->values();

        $shipments = Shipment::query()
            ->with([
                'vehicle',
                'route.originNode',
                'route.destinationNode',
                'fromOrganization',
                'toOrganization',
                'originNode',
                'destinationNode',
                'items.batch.product',
            ])
            ->whereIn('status', [
                ShipmentStatus::Dispatched,
                ShipmentStatus::InTransit,
                ShipmentStatus::Arrived,
                ShipmentStatus::Delayed,
                ShipmentStatus::Delivered,
            ])
            ->latest('updated_at')
            ->limit(40)
            ->get()
            ->map(fn (Shipment $shipment) => $this->serializeShipment($shipment));

        $vehicles = Vehicle::query()
            ->with(['organization'])
            ->get()
            ->map(function (Vehicle $vehicle) {
                $position = VehiclePosition::query()
                    ->where('vehicle_id', $vehicle->id)
                    ->orderByDesc('recorded_at')
                    ->first();

                return [
                    'id' => $vehicle->id,
                    'registration' => $vehicle->registration,
                    'type' => $vehicle->type->value,
                    'status' => $vehicle->status->value,
                    'driver' => $vehicle->driver_name,
                    'capacity_kg' => $vehicle->capacity_kg,
                    'lat' => $position ? (float) $position->latitude : null,
                    'lng' => $position ? (float) $position->longitude : null,
                    'speed_kmh' => $position?->speed_kmh,
                    'temperature_c' => $position?->temperature_c,
                    'recorded_at' => $position?->recorded_at?->toIso8601String(),
                    'shipment_id' => $position?->shipment_id,
                ];
            });

        $activity = Shipment::query()
            ->with(['fromOrganization', 'toOrganization', 'vehicle'])
            ->latest('updated_at')
            ->limit(15)
            ->get()
            ->map(fn (Shipment $s) => [
                'id' => $s->id,
                'code' => $s->code,
                'status' => $s->status->value,
                'status_label' => $s->status->label(),
                'from' => $s->fromOrganization?->name,
                'to' => $s->toOrganization?->name,
                'vehicle' => $s->vehicle?->registration,
                'updated_at' => $s->updated_at?->toIso8601String(),
            ]);

        return [
            'kpis' => $this->snapshot(),
            'nodes' => $nodes,
            'shipments' => $shipments,
            'vehicles' => $vehicles,
            'activity' => $activity,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeShipment(Shipment $shipment): array
    {
        $route = $shipment->route;
        $waypoints = $route?->waypoints ?? [];

        $path = [];
        if ($shipment->originNode) {
            $olat = $shipment->originNode->mapLatitude();
            $olng = $shipment->originNode->mapLongitude();
            if ($olat !== null && $olng !== null) {
                $path[] = ['lat' => $olat, 'lng' => $olng, 'label' => $shipment->originNode->name];
            }
        }
        foreach ($waypoints as $wp) {
            if (isset($wp['lat'], $wp['lng'])) {
                $path[] = [
                    'lat' => (float) $wp['lat'],
                    'lng' => (float) $wp['lng'],
                    'label' => $wp['label'] ?? null,
                ];
            }
        }
        if ($shipment->destinationNode) {
            $dlat = $shipment->destinationNode->mapLatitude();
            $dlng = $shipment->destinationNode->mapLongitude();
            if ($dlat !== null && $dlng !== null) {
                $path[] = ['lat' => $dlat, 'lng' => $dlng, 'label' => $shipment->destinationNode->name];
            }
        }

        $batches = $shipment->items->map(fn ($item) => [
            'batch_id' => $item->batch_id,
            'batch_code' => $item->batch?->code,
            'product' => $item->batch?->product?->name,
            'quantity' => $item->quantity,
            'unit' => $item->unit,
        ])->values();

        return [
            'id' => $shipment->id,
            'code' => $shipment->code,
            'status' => $shipment->status->value,
            'status_label' => $shipment->status->label(),
            'from' => $shipment->fromOrganization?->name,
            'to' => $shipment->toOrganization?->name,
            'origin' => $shipment->originNode?->name ?? $shipment->fromLocation?->city,
            'destination' => $shipment->destinationNode?->name ?? $shipment->toLocation?->city,
            'vehicle' => $shipment->vehicle?->registration,
            'driver' => $shipment->vehicle?->driver_name,
            'load_kg' => $shipment->load_kg,
            'eta_at' => $shipment->eta_at?->toIso8601String(),
            'temperature_c' => $shipment->current_temperature_c,
            'estimated_co2e_kg' => $shipment->estimated_co2e_kg,
            'route_status' => $route?->status?->value,
            'distance_km' => $route?->distance_km,
            'path' => $path,
            'batches' => $batches,
        ];
    }
}
