<?php

namespace App\Actions\Shipments;

use App\Domain\Identity\AuditLogger;
use App\Enums\RouteStatus;
use App\Enums\ShipmentStatus;
use App\Enums\TraceabilityEventType;
use App\Enums\VehicleStatus;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReceiveShipmentAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $actor, Shipment $shipment, array $data = []): Shipment
    {
        if (! $shipment->status->isActive() && $shipment->status !== ShipmentStatus::Arrived) {
            throw new InvalidArgumentException('Ce shipment ne peut pas être réceptionné dans son état actuel.');
        }

        return DB::transaction(function () use ($actor, $shipment, $data) {
            $shipment->loadMissing(['items.batch', 'vehicle', 'route', 'distribution']);

            $deliveredAt = $data['delivered_at'] ?? now();
            $receivedQtyFactor = isset($data['received_quantity_factor'])
                ? (float) $data['received_quantity_factor']
                : 1.0;

            $shipment->update([
                'status' => ShipmentStatus::Delivered,
                'delivered_at' => $deliveredAt,
                'current_temperature_c' => $data['current_temperature_c'] ?? $shipment->current_temperature_c,
            ]);

            if ($shipment->vehicle) {
                $shipment->vehicle->update(['status' => VehicleStatus::Available]);
            }

            if ($shipment->route) {
                $actualMin = null;
                if ($shipment->dispatched_at) {
                    $actualMin = $shipment->dispatched_at->diffInMinutes($deliveredAt);
                }
                $shipment->route->update([
                    'status' => RouteStatus::Completed,
                    'actual_duration_min' => $actualMin,
                ]);
            }

            if ($shipment->distribution && ! $shipment->distribution->received_at) {
                $shipment->distribution->update(['received_at' => $deliveredAt]);
            }

            foreach ($shipment->items as $item) {
                $batch = $item->batch;
                if (! $batch) {
                    continue;
                }

                $qty = round((float) $item->quantity * $receivedQtyFactor, 3);

                $batch->traceabilityEvents()->create([
                    'type' => TraceabilityEventType::Arrived,
                    'organization_id' => $shipment->to_organization_id,
                    'location_id' => $shipment->to_location_id,
                    'actor_user_id' => $actor->id,
                    'occurred_at' => $deliveredAt,
                    'quantity' => $qty,
                    'unit' => $item->unit,
                    'title' => 'Arrivée '.$shipment->code,
                    'meta' => ['shipment_id' => $shipment->id],
                ]);

                $toName = $shipment->toOrganization?->name
                    ?? $shipment->toOrganization()->value('name');

                $batch->traceabilityEvents()->create([
                    'type' => TraceabilityEventType::Delivered,
                    'organization_id' => $shipment->to_organization_id,
                    'location_id' => $shipment->to_location_id,
                    'actor_user_id' => $actor->id,
                    'occurred_at' => $deliveredAt,
                    'quantity' => $qty,
                    'unit' => $item->unit,
                    'title' => 'Livraison '.$shipment->code.($toName ? ' — '.$toName : ''),
                    'meta' => [
                        'shipment_id' => $shipment->id,
                        'shipment_code' => $shipment->code,
                        'from_organization_id' => $shipment->from_organization_id,
                    ],
                ]);

                $batch->traceabilityEvents()->create([
                    'type' => TraceabilityEventType::Receipt,
                    'organization_id' => $shipment->to_organization_id,
                    'location_id' => $shipment->to_location_id,
                    'actor_user_id' => $actor->id,
                    'occurred_at' => $deliveredAt,
                    'quantity' => $qty,
                    'unit' => $item->unit,
                    'title' => 'Réception'.($toName ? ' par '.$toName : ''),
                    'meta' => [
                        'shipment_id' => $shipment->id,
                        'from_organization_id' => $shipment->from_organization_id,
                    ],
                ]);
            }

            $this->audit->log($actor, 'shipment.received', $shipment, null, [
                'code' => $shipment->code,
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
