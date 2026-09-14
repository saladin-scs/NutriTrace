<?php

namespace App\Actions\Shipments;

use App\Domain\Distribution\EnvironmentalImpactEstimator;
use App\Domain\Distribution\ShipmentCodeAllocator;
use App\Domain\Identity\AuditLogger;
use App\Enums\ShipmentStatus;
use App\Models\Batch;
use App\Models\Distribution;
use App\Models\DistributionNode;
use App\Models\Organization;
use App\Models\Route as LogisticsRoute;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateShipmentAction
{
    public function __construct(
        private ShipmentCodeAllocator $codes,
        private EnvironmentalImpactEstimator $impact,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{batch_id:int, quantity?:float|int|string, unit?:string}>  $items
     */
    public function execute(User $actor, array $data, array $items): Shipment
    {
        if ($items === []) {
            throw new InvalidArgumentException('Un shipment doit contenir au moins un lot.');
        }

        return DB::transaction(function () use ($actor, $data, $items) {
            $vehicle = isset($data['vehicle_id'])
                ? Vehicle::query()->find($data['vehicle_id'])
                : null;

            $route = isset($data['route_id'])
                ? LogisticsRoute::query()->find($data['route_id'])
                : null;

            $distribution = isset($data['distribution_id'])
                ? Distribution::query()->find($data['distribution_id'])
                : null;

            $originNodeId = $data['origin_node_id'] ?? $route?->origin_node_id;
            $destinationNodeId = $data['destination_node_id'] ?? $route?->destination_node_id;

            $originNode = $originNodeId
                ? DistributionNode::query()->with('organization')->find($originNodeId)
                : null;
            $destinationNode = $destinationNodeId
                ? DistributionNode::query()->with('organization')->find($destinationNodeId)
                : null;

            $fromOrgId = $data['from_organization_id']
                ?? $distribution?->from_organization_id
                ?? $originNode?->organization_id;
            $toOrgId = $data['to_organization_id']
                ?? $distribution?->to_organization_id
                ?? $destinationNode?->organization_id;

            $fromLocationId = $data['from_location_id']
                ?? $distribution?->from_location_id
                ?? $originNode?->location_id
                ?? ($fromOrgId ? Organization::query()->whereKey($fromOrgId)->value('primary_location_id') : null);
            $toLocationId = $data['to_location_id']
                ?? $distribution?->to_location_id
                ?? $destinationNode?->location_id
                ?? ($toOrgId ? Organization::query()->whereKey($toOrgId)->value('primary_location_id') : null);

            $totalQty = 0.0;
            $unit = $data['unit'] ?? 'kg';
            $normalizedItems = [];

            foreach ($items as $item) {
                $batch = Batch::query()->with('product')->findOrFail($item['batch_id']);
                $qty = (float) ($item['quantity'] ?? $batch->quantity);
                $itemUnit = $item['unit'] ?? $batch->unit ?? $unit;
                $totalQty += $qty;
                $normalizedItems[] = [
                    'batch' => $batch,
                    'quantity' => $qty,
                    'unit' => $itemUnit,
                ];
            }

            $loadKg = isset($data['load_kg']) ? (float) $data['load_kg'] : $totalQty;
            $distance = $route?->distance_km !== null
                ? (float) $route->distance_km
                : (isset($data['distance_km']) ? (float) $data['distance_km'] : null);

            $shipment = Shipment::query()->create([
                'code' => $this->codes->allocate(),
                'status' => ShipmentStatus::Draft,
                'distribution_channel_id' => $data['distribution_channel_id'] ?? null,
                'distribution_id' => $distribution?->id,
                'vehicle_id' => $vehicle?->id,
                'route_id' => $route?->id,
                'from_organization_id' => $fromOrgId,
                'to_organization_id' => $toOrgId,
                'from_location_id' => $fromLocationId,
                'to_location_id' => $toLocationId,
                'origin_node_id' => $originNode?->id,
                'destination_node_id' => $destinationNode?->id,
                'created_by' => $actor->id,
                'total_quantity' => $totalQty,
                'unit' => $unit,
                'load_kg' => $loadKg,
                'eta_at' => $data['eta_at'] ?? null,
                'estimated_co2e_kg' => $this->impact->estimateCo2eKg($distance, $vehicle, $loadKg),
                'current_temperature_c' => $data['current_temperature_c'] ?? null,
                'notes' => $data['notes'] ?? null,
                'meta' => $data['meta'] ?? null,
            ]);

            foreach ($normalizedItems as $row) {
                $shipment->items()->create([
                    'batch_id' => $row['batch']->id,
                    'quantity' => $row['quantity'],
                    'unit' => $row['unit'],
                ]);
            }

            $this->audit->log($actor, 'shipment.created', $shipment, null, [
                'code' => $shipment->code,
                'items' => count($normalizedItems),
            ]);

            return $shipment->fresh([
                'items.batch.product',
                'vehicle',
                'route',
                'fromOrganization',
                'toOrganization',
                'originNode',
                'destinationNode',
            ]);
        });
    }
}
