<?php

namespace App\Actions\ColdRooms;

use App\Domain\ColdChain\OccupancyService;
use App\Domain\Identity\AuditLogger;
use App\Enums\ColdRoomMovementType;
use App\Enums\StockMovementType;
use App\Enums\StorageRecordStatus;
use App\Enums\TraceabilityEventType;
use App\Events\StockMovementRecorded;
use App\Models\Batch;
use App\Models\ColdRoom;
use App\Models\ColdRoomMovement;
use App\Models\StockMovement;
use App\Models\StorageRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Records a cold-room physical flow and maintains:
 * movement log + storage ledger + stock ledger + occupancy + traceability.
 */
class RecordColdRoomMovementAction
{
    public function __construct(
        private AuditLogger $audit,
        private OccupancyService $occupancy,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $actor, ColdRoom $room, array $data): ColdRoomMovement
    {
        $type = $data['type'] instanceof ColdRoomMovementType
            ? $data['type']
            : ColdRoomMovementType::from($data['type']);

        $batch = isset($data['batch_id'])
            ? Batch::query()->findOrFail($data['batch_id'])
            : null;

        if ($type === ColdRoomMovementType::Loss && $batch === null) {
            throw ValidationException::withMessages([
                'batch_id' => 'Une perte doit être liée à un lot pour l’audit.',
            ]);
        }

        return DB::transaction(function () use ($actor, $room, $data, $type, $batch) {
            $qty = (float) $data['quantity'];
            $occurredAt = $data['occurred_at'] ?? now();
            $unit = $data['unit'] ?? ($batch?->unit ?? 'kg');
            $duration = $data['duration_minutes'] ?? null;

            if ($type->isInbound()) {
                try {
                    $this->occupancy->assertCapacity($room->fresh(), $qty);
                } catch (InvalidArgumentException $e) {
                    throw ValidationException::withMessages([
                        'quantity' => $e->getMessage(),
                    ]);
                }
            }

            if ($type->isOutbound() && $batch && $duration === null) {
                $open = StorageRecord::query()
                    ->where('cold_room_id', $room->id)
                    ->where('batch_id', $batch->id)
                    ->whereIn('status', [
                        StorageRecordStatus::Stored->value,
                        StorageRecordStatus::Partial->value,
                    ])
                    ->where('remaining_quantity', '>', 0)
                    ->orderBy('entered_at')
                    ->first();

                if ($open) {
                    $duration = max(0, $open->entered_at->diffInMinutes($occurredAt));
                }
            }

            $traceEvent = null;
            if ($batch) {
                $traceType = match (true) {
                    $type === ColdRoomMovementType::Loss => TraceabilityEventType::Loss,
                    $type->isInbound() => TraceabilityEventType::ColdStorageEntry,
                    $type->isOutbound() => TraceabilityEventType::ColdStorageExit,
                    default => TraceabilityEventType::Stored,
                };

                $traceEvent = $batch->traceabilityEvents()->create([
                    'type' => $traceType,
                    'organization_id' => $room->organization_id,
                    'location_id' => $room->location_id,
                    'actor_user_id' => $actor->id,
                    'occurred_at' => $occurredAt,
                    'quantity' => $qty,
                    'unit' => $unit,
                    'title' => ($data['event_label'] ?? $type->label()).' — '.$room->name,
                    'meta' => [
                        'cold_room_id' => $room->id,
                        'temperature_c' => $data['temperature_c'] ?? $room->current_temperature_c,
                        'humidity_pct' => $data['humidity_pct'] ?? null,
                        'who' => $actor->name,
                        'where' => $room->code,
                    ],
                ]);
            }

            $movement = ColdRoomMovement::query()->create([
                'cold_room_id' => $room->id,
                'batch_id' => $batch?->id,
                'product_id' => $data['product_id'] ?? $batch?->product_id,
                'type' => $type,
                'occurred_at' => $occurredAt,
                'actor_user_id' => $actor->id,
                'from_organization_id' => $data['from_organization_id']
                    ?? ($type->isInbound() ? null : $room->organization_id),
                'from_location_id' => $data['from_location_id'] ?? null,
                'from_cold_room_id' => $data['from_cold_room_id']
                    ?? ($type->isOutbound() ? $room->id : null),
                'to_organization_id' => $data['to_organization_id']
                    ?? ($type->isOutbound() ? null : $room->organization_id),
                'to_location_id' => $data['to_location_id'] ?? $room->location_id,
                'to_cold_room_id' => $data['to_cold_room_id']
                    ?? ($type->isInbound() ? $room->id : null),
                'quantity' => $qty,
                'unit' => $unit,
                'temperature_c' => $data['temperature_c'] ?? $room->current_temperature_c,
                'humidity_pct' => $data['humidity_pct'] ?? null,
                'duration_minutes' => $duration,
                'event_label' => $data['event_label'] ?? $type->label(),
                'notes' => $data['notes'] ?? null,
                'traceability_event_id' => $traceEvent?->id,
            ]);

            $storage = $this->syncStorageLedger($actor, $room, $batch, $type, $qty, $unit, $occurredAt, $data);
            $stock = $this->syncStockLedger($actor, $room, $batch, $type, $qty, $unit, $occurredAt, $storage, $data);

            if ($data['temperature_c'] ?? null) {
                $room->update(['current_temperature_c' => $data['temperature_c']]);
            }

            $this->occupancy->recalculate($room);

            StockMovementRecorded::dispatch($stock);

            $this->audit->log($actor, 'cold_room.movement_recorded', $movement, null, [
                'cold_room' => $room->code,
                'type' => $type->value,
                'batch' => $batch?->code,
                'storage_record_id' => $storage?->id,
                'stock_movement_id' => $stock->id,
            ]);

            return $movement->fresh([
                'coldRoom', 'batch', 'actor',
                'fromOrganization', 'toOrganization',
                'fromLocation', 'toLocation',
                'fromColdRoom', 'toColdRoom',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncStorageLedger(
        User $actor,
        ColdRoom $room,
        ?Batch $batch,
        ColdRoomMovementType $type,
        float $qty,
        string $unit,
        mixed $occurredAt,
        array $data,
    ): ?StorageRecord {
        if (! $batch) {
            return null;
        }

        if ($type->isInbound()) {
            return StorageRecord::query()->create([
                'cold_room_id' => $room->id,
                'batch_id' => $batch->id,
                'product_id' => $batch->product_id,
                'quantity' => $qty,
                'remaining_quantity' => $qty,
                'unit' => $unit,
                'status' => StorageRecordStatus::Stored,
                'entered_at' => $occurredAt,
                'entered_by' => $actor->id,
                'source_organization_id' => $data['from_organization_id'] ?? null,
                'source_location_id' => $data['from_location_id'] ?? null,
                'reason' => $data['event_label'] ?? $type->label(),
            ]);
        }

        if (! $type->isOutbound() && $type !== ColdRoomMovementType::Loss) {
            return null;
        }

        $remainingToRemove = $qty;
        $lastTouched = null;

        // FEFO: consume oldest-expiring open storage first.
        $opens = StorageRecord::query()
            ->where('cold_room_id', $room->id)
            ->where('batch_id', $batch->id)
            ->whereIn('status', [
                StorageRecordStatus::Stored->value,
                StorageRecordStatus::Partial->value,
            ])
            ->where('remaining_quantity', '>', 0)
            ->with('batch')
            ->get()
            ->sortBy(fn (StorageRecord $r) => $r->batch?->expires_at?->timestamp ?? PHP_INT_MAX)
            ->values();

        foreach ($opens as $open) {
            if ($remainingToRemove <= 0) {
                break;
            }

            $take = min((float) $open->remaining_quantity, $remainingToRemove);
            $newRemaining = round((float) $open->remaining_quantity - $take, 3);

            $status = $type === ColdRoomMovementType::Loss
                ? StorageRecordStatus::Lost
                : ($newRemaining <= 0.001 ? StorageRecordStatus::Released : StorageRecordStatus::Partial);

            $open->update([
                'remaining_quantity' => max(0, $newRemaining),
                'status' => $status,
                'removed_at' => $newRemaining <= 0.001 ? $occurredAt : $open->removed_at,
                'removed_by' => $actor->id,
                'destination_organization_id' => $data['to_organization_id'] ?? $open->destination_organization_id,
                'destination_location_id' => $data['to_location_id'] ?? $open->destination_location_id,
                'reason' => $data['event_label'] ?? $type->label(),
            ]);

            $remainingToRemove = round($remainingToRemove - $take, 3);
            $lastTouched = $open->fresh();
        }

        if ($remainingToRemove > 0.001) {
            throw ValidationException::withMessages([
                'quantity' => sprintf(
                    'Stock insuffisant en chambre froide pour le lot %s (manque %.3f %s).',
                    $batch->code,
                    $remainingToRemove,
                    $unit
                ),
            ]);
        }

        return $lastTouched;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncStockLedger(
        User $actor,
        ColdRoom $room,
        ?Batch $batch,
        ColdRoomMovementType $type,
        float $qty,
        string $unit,
        mixed $occurredAt,
        ?StorageRecord $storage,
        array $data,
    ): StockMovement {
        $stockType = match ($type) {
            ColdRoomMovementType::Entry, ColdRoomMovementType::TransferIn => StockMovementType::StockIn,
            ColdRoomMovementType::Exit, ColdRoomMovementType::TransferOut => StockMovementType::StockOut,
            ColdRoomMovementType::Loss => StockMovementType::Loss,
            ColdRoomMovementType::InventoryAdjust => StockMovementType::Relocation,
        };

        return StockMovement::query()->create([
            'type' => $stockType,
            'product_id' => $data['product_id'] ?? $batch?->product_id,
            'batch_id' => $batch?->id,
            'quantity' => $qty,
            'unit' => $unit,
            'actor_user_id' => $actor->id,
            'occurred_at' => $occurredAt,
            'cold_room_id' => $room->id,
            'warehouse_location_id' => $room->location_id,
            'source_organization_id' => $data['from_organization_id']
                ?? ($type->isOutbound() ? $room->organization_id : null),
            'source_location_id' => $data['from_location_id'] ?? ($type->isOutbound() ? $room->location_id : null),
            'destination_organization_id' => $data['to_organization_id']
                ?? ($type->isInbound() ? $room->organization_id : null),
            'destination_location_id' => $data['to_location_id'] ?? ($type->isInbound() ? $room->location_id : null),
            'storage_record_id' => $storage?->id,
            'shipment_id' => $data['shipment_id'] ?? null,
            'vehicle_id' => $data['vehicle_id'] ?? null,
            'reason' => $data['event_label'] ?? $type->label(),
            'reference_document' => $data['reference_document'] ?? null,
            'meta' => [
                'who' => $actor->name,
                'when' => now()->toIso8601String(),
                'where' => $room->code,
                'cold_room_movement_type' => $type->value,
            ],
        ]);
    }
}
