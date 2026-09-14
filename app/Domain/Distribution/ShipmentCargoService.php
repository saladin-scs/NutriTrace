<?php

namespace App\Domain\Distribution;

use App\Actions\ColdRooms\RecordColdRoomMovementAction;
use App\Enums\ColdRoomMovementType;
use App\Enums\StockMovementType;
use App\Enums\StorageRecordStatus;
use App\Events\StockMovementRecorded;
use App\Models\Batch;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\StockMovement;
use App\Models\StorageRecord;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Moves lot/product quantities between orgs (and optional cold rooms) with a shipment.
 */
final class ShipmentCargoService
{
    public function __construct(
        private RecordColdRoomMovementAction $coldRoomMovements,
    ) {}

    public function recordDispatch(User $actor, Shipment $shipment, ShipmentItem $item, mixed $occurredAt): void
    {
        $batch = $item->batch;
        if (! $batch) {
            return;
        }

        $qty = (float) $item->quantity;

        $stock = StockMovement::query()->create([
            'type' => StockMovementType::Shipment,
            'product_id' => $batch->product_id,
            'batch_id' => $batch->id,
            'quantity' => $qty,
            'unit' => $item->unit,
            'actor_user_id' => $actor->id,
            'occurred_at' => $occurredAt,
            'source_organization_id' => $shipment->from_organization_id,
            'source_location_id' => $shipment->from_location_id,
            'destination_organization_id' => $shipment->to_organization_id,
            'destination_location_id' => $shipment->to_location_id,
            'vehicle_id' => $shipment->vehicle_id,
            'shipment_id' => $shipment->id,
            'reference_document' => $shipment->code,
            'reason' => 'Expédition '.$shipment->code,
            'meta' => [
                'phase' => 'dispatch',
                'batch_code' => $batch->code,
                'product' => $batch->product?->name,
            ],
        ]);

        StockMovementRecorded::dispatch($stock);

        $originRoom = $shipment->originNode?->coldRoom;
        if ($originRoom) {
            $available = $this->availableInRoom($originRoom->id, $batch->id);
            if ($available >= $qty) {
                try {
                    $this->coldRoomMovements->execute($actor, $originRoom, [
                        'type' => ColdRoomMovementType::TransferOut,
                        'batch_id' => $batch->id,
                        'quantity' => $qty,
                        'unit' => $item->unit,
                        'occurred_at' => $occurredAt,
                        'from_organization_id' => $shipment->from_organization_id,
                        'to_organization_id' => $shipment->to_organization_id,
                        'to_location_id' => $shipment->to_location_id,
                        'shipment_id' => $shipment->id,
                        'vehicle_id' => $shipment->vehicle_id,
                        'event_label' => 'Sortie pour shipment '.$shipment->code,
                        'reference_document' => $shipment->code,
                    ]);
                } catch (ValidationException) {
                    // Stock ledger already recorded; cold-room sync is best-effort.
                }
            }
        }
    }

    public function recordReceive(
        User $actor,
        Shipment $shipment,
        ShipmentItem $item,
        float $qty,
        mixed $occurredAt,
    ): void {
        $batch = $item->batch;
        if (! $batch) {
            return;
        }

        if ($shipment->to_organization_id) {
            $batch->update([
                'organization_id' => $shipment->to_organization_id,
            ]);
        }

        $stock = StockMovement::query()->create([
            'type' => StockMovementType::Reception,
            'product_id' => $batch->product_id,
            'batch_id' => $batch->id,
            'quantity' => $qty,
            'unit' => $item->unit,
            'actor_user_id' => $actor->id,
            'occurred_at' => $occurredAt,
            'source_organization_id' => $shipment->from_organization_id,
            'source_location_id' => $shipment->from_location_id,
            'destination_organization_id' => $shipment->to_organization_id,
            'destination_location_id' => $shipment->to_location_id,
            'vehicle_id' => $shipment->vehicle_id,
            'shipment_id' => $shipment->id,
            'reference_document' => $shipment->code,
            'reason' => 'Réception '.$shipment->code,
            'meta' => [
                'phase' => 'receive',
                'batch_code' => $batch->code,
                'product' => $batch->product?->name,
            ],
        ]);

        StockMovementRecorded::dispatch($stock);

        $destRoom = $shipment->destinationNode?->coldRoom;
        if ($destRoom) {
            try {
                $this->coldRoomMovements->execute($actor, $destRoom, [
                    'type' => ColdRoomMovementType::TransferIn,
                    'batch_id' => $batch->id,
                    'quantity' => $qty,
                    'unit' => $item->unit,
                    'occurred_at' => $occurredAt,
                    'from_organization_id' => $shipment->from_organization_id,
                    'from_location_id' => $shipment->from_location_id,
                    'to_organization_id' => $shipment->to_organization_id,
                    'shipment_id' => $shipment->id,
                    'vehicle_id' => $shipment->vehicle_id,
                    'event_label' => 'Entrée après shipment '.$shipment->code,
                    'reference_document' => $shipment->code,
                ]);
            } catch (ValidationException) {
                // Capacity issues should not roll back delivery ownership transfer.
            }
        }
    }

    private function availableInRoom(int $coldRoomId, int $batchId): float
    {
        return (float) StorageRecord::query()
            ->where('cold_room_id', $coldRoomId)
            ->where('batch_id', $batchId)
            ->whereIn('status', [
                StorageRecordStatus::Stored->value,
                StorageRecordStatus::Partial->value,
            ])
            ->sum('remaining_quantity');
    }
}
