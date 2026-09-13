<?php

namespace App\Actions\Shipments;

use App\Domain\Identity\AuditLogger;
use App\Enums\BatchStatus;
use App\Enums\RouteStatus;
use App\Enums\ShipmentStatus;
use App\Enums\TraceabilityEventType;
use App\Enums\VehicleStatus;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DispatchShipmentAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $actor, Shipment $shipment, array $data = []): Shipment
    {
        if (! in_array($shipment->status, [ShipmentStatus::Draft, ShipmentStatus::Delayed], true)) {
            throw new InvalidArgumentException('Seuls les shipments brouillon ou retardés peuvent être expédiés.');
        }

        return DB::transaction(function () use ($actor, $shipment, $data) {
            $shipment->loadMissing(['items.batch', 'vehicle', 'route']);

            $status = ! empty($data['delayed'])
                ? ShipmentStatus::Delayed
                : ShipmentStatus::InTransit;

            $dispatchedAt = isset($data['dispatched_at'])
                ? $data['dispatched_at']
                : now();

            $shipment->update([
                'status' => $status,
                'dispatched_at' => $dispatchedAt,
                'eta_at' => $data['eta_at'] ?? $shipment->eta_at,
                'current_temperature_c' => $data['current_temperature_c'] ?? $shipment->current_temperature_c,
                'vehicle_id' => $data['vehicle_id'] ?? $shipment->vehicle_id,
            ]);

            if ($shipment->vehicle) {
                $shipment->vehicle->update(['status' => VehicleStatus::InTransit]);
            }

            if ($shipment->route) {
                $shipment->route->update([
                    'status' => $status === ShipmentStatus::Delayed
                        ? RouteStatus::Delayed
                        : RouteStatus::Active,
                ]);
            }

            foreach ($shipment->items as $item) {
                $batch = $item->batch;
                if (! $batch) {
                    continue;
                }

                $batch->update(['status' => BatchStatus::Distributed]);

                $batch->traceabilityEvents()->create([
                    'type' => TraceabilityEventType::Loaded,
                    'organization_id' => $shipment->from_organization_id,
                    'location_id' => $shipment->from_location_id,
                    'actor_user_id' => $actor->id,
                    'occurred_at' => $dispatchedAt,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'title' => 'Chargement '.$shipment->code,
                    'meta' => [
                        'shipment_id' => $shipment->id,
                        'shipment_code' => $shipment->code,
                        'vehicle_id' => $shipment->vehicle_id,
                    ],
                ]);

                $batch->traceabilityEvents()->create([
                    'type' => TraceabilityEventType::Dispatched,
                    'organization_id' => $shipment->from_organization_id,
                    'location_id' => $shipment->from_location_id,
                    'actor_user_id' => $actor->id,
                    'occurred_at' => $dispatchedAt,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'title' => 'Expédition '.$shipment->code,
                    'meta' => [
                        'shipment_id' => $shipment->id,
                        'shipment_code' => $shipment->code,
                        'to_organization_id' => $shipment->to_organization_id,
                    ],
                ]);

                $batch->traceabilityEvents()->create([
                    'type' => TraceabilityEventType::InTransit,
                    'organization_id' => $shipment->from_organization_id,
                    'location_id' => $shipment->from_location_id,
                    'actor_user_id' => $actor->id,
                    'occurred_at' => $dispatchedAt,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'title' => 'En transit — '.$shipment->code,
                    'meta' => [
                        'shipment_id' => $shipment->id,
                        'vehicle' => $shipment->vehicle?->registration,
                    ],
                ]);
            }

            $this->audit->log($actor, 'shipment.dispatched', $shipment, null, [
                'code' => $shipment->code,
                'status' => $status->value,
            ]);

            return $shipment->fresh([
                'items.batch.product',
                'vehicle',
                'route',
                'fromOrganization',
                'toOrganization',
            ]);
        });
    }
}
