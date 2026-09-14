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
                'route.originNode.location',
                'route.destinationNode.location',
                'fromOrganization.primaryLocation',
                'toOrganization.primaryLocation',
                'fromLocation',
                'toLocation',
                'originNode.location',
                'destinationNode.location',
                'items.batch.product',
                'positions' => fn ($q) => $q->latest('recorded_at')->limit(1),
            ])
            ->whereIn('status', [
                ShipmentStatus::Dispatched,
                ShipmentStatus::InTransit,
                ShipmentStatus::Arrived,
                ShipmentStatus::Delayed,
                ShipmentStatus::Delivered,
            ])
            ->latest('updated_at')
            ->limit(50)
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
            ->with(['fromOrganization', 'toOrganization', 'vehicle', 'items.batch.product'])
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
                'product_summary' => $this->productSummary($s),
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
        $shipment->loadMissing([
            'vehicle',
            'route.originNode.location',
            'route.destinationNode.location',
            'fromOrganization.primaryLocation',
            'toOrganization.primaryLocation',
            'fromLocation',
            'toLocation',
            'originNode.location',
            'destinationNode.location',
            'items.batch.product',
            'positions' => fn ($q) => $q->latest('recorded_at')->limit(1),
        ]);

        $route = $shipment->route;
        $waypoints = $route?->waypoints ?? [];
        $path = $this->buildPath($shipment, $waypoints);

        $batches = $shipment->items->map(fn ($item) => [
            'batch_id' => $item->batch_id,
            'batch_code' => $item->batch?->code,
            'product' => $item->batch?->product?->name,
            'product_id' => $item->batch?->product_id,
            'quantity' => (float) $item->quantity,
            'unit' => $item->unit,
        ])->values();

        $isActive = $shipment->status->isActive()
            || $shipment->status === ShipmentStatus::Arrived;

        $position = $this->resolveCurrentPosition($shipment, $path);

        return [
            'id' => $shipment->id,
            'code' => $shipment->code,
            'status' => $shipment->status->value,
            'status_label' => $shipment->status->label(),
            'is_active' => $isActive,
            'from' => $shipment->fromOrganization?->name,
            'to' => $shipment->toOrganization?->name,
            'origin' => $shipment->originNode?->name
                ?? $shipment->fromLocation?->city
                ?? $shipment->fromOrganization?->name,
            'destination' => $shipment->destinationNode?->name
                ?? $shipment->toLocation?->city
                ?? $shipment->toOrganization?->name,
            'origin_node_id' => $shipment->origin_node_id,
            'destination_node_id' => $shipment->destination_node_id,
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
            'batch_count' => $batches->count(),
            'product_summary' => $this->productSummary($shipment),
            'current_lat' => $position['lat'] ?? null,
            'current_lng' => $position['lng'] ?? null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $waypoints
     * @return list<array{lat:float,lng:float,label:?string}>
     */
    private function buildPath(Shipment $shipment, array $waypoints): array
    {
        $path = [];

        $push = function (?float $lat, ?float $lng, ?string $label) use (&$path): void {
            if ($lat === null || $lng === null) {
                return;
            }
            $path[] = ['lat' => $lat, 'lng' => $lng, 'label' => $label];
        };

        if ($shipment->originNode) {
            $push(
                $shipment->originNode->mapLatitude(),
                $shipment->originNode->mapLongitude(),
                $shipment->originNode->name
            );
        } else {
            $from = $shipment->fromLocation ?? $shipment->fromOrganization?->primaryLocation;
            $push(
                $from?->latitude !== null ? (float) $from->latitude : null,
                $from?->longitude !== null ? (float) $from->longitude : null,
                $from?->city ?? $shipment->fromOrganization?->name
            );
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
            $push(
                $shipment->destinationNode->mapLatitude(),
                $shipment->destinationNode->mapLongitude(),
                $shipment->destinationNode->name
            );
        } else {
            $to = $shipment->toLocation ?? $shipment->toOrganization?->primaryLocation;
            $push(
                $to?->latitude !== null ? (float) $to->latitude : null,
                $to?->longitude !== null ? (float) $to->longitude : null,
                $to?->city ?? $shipment->toOrganization?->name
            );
        }

        return $path;
    }

    /**
     * @param  list<array{lat:float,lng:float,label:?string}>  $path
     * @return array{lat:?float,lng:?float}
     */
    private function resolveCurrentPosition(Shipment $shipment, array $path): array
    {
        $latest = $shipment->positions->first();
        if ($latest) {
            return [
                'lat' => (float) $latest->latitude,
                'lng' => (float) $latest->longitude,
            ];
        }

        if ($shipment->vehicle_id) {
            $vp = VehiclePosition::query()
                ->where('vehicle_id', $shipment->vehicle_id)
                ->when($shipment->id, fn ($q) => $q->where(function ($inner) use ($shipment) {
                    $inner->where('shipment_id', $shipment->id)->orWhereNull('shipment_id');
                }))
                ->orderByDesc('recorded_at')
                ->first();
            if ($vp) {
                return [
                    'lat' => (float) $vp->latitude,
                    'lng' => (float) $vp->longitude,
                ];
            }
        }

        if (count($path) >= 2 && $shipment->status->isActive()) {
            $mid = (int) floor((count($path) - 1) / 2);
            $a = $path[$mid];
            $b = $path[min($mid + 1, count($path) - 1)];

            return [
                'lat' => ($a['lat'] + $b['lat']) / 2,
                'lng' => ($a['lng'] + $b['lng']) / 2,
            ];
        }

        if ($path !== []) {
            return ['lat' => $path[0]['lat'], 'lng' => $path[0]['lng']];
        }

        return ['lat' => null, 'lng' => null];
    }

    private function productSummary(Shipment $shipment): string
    {
        $shipment->loadMissing('items.batch.product');
        $names = $shipment->items
            ->map(fn ($i) => $i->batch?->product?->name)
            ->filter()
            ->unique()
            ->values();

        $count = $shipment->items->count();
        if ($names->isEmpty()) {
            return $count.' lot'.($count > 1 ? 's' : '');
        }

        $label = $names->take(2)->implode(', ');
        if ($names->count() > 2) {
            $label .= ' +'.($names->count() - 2);
        }

        return $count.' lot'.($count > 1 ? 's' : '').' · '.$label;
    }
}
