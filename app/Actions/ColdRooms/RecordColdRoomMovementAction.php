<?php

namespace App\Actions\ColdRooms;

use App\Domain\Identity\AuditLogger;
use App\Enums\ColdRoomMovementType;
use App\Enums\TraceabilityEventType;
use App\Models\Batch;
use App\Models\ColdRoom;
use App\Models\ColdRoomMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordColdRoomMovementAction
{
    public function __construct(private AuditLogger $audit) {}

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
            $occurredAt = $data['occurred_at'] ?? now();
            $duration = $data['duration_minutes'] ?? null;

            if ($type->isOutbound() && $batch && $duration === null) {
                $lastEntry = ColdRoomMovement::query()
                    ->where('cold_room_id', $room->id)
                    ->where('batch_id', $batch->id)
                    ->whereIn('type', [
                        ColdRoomMovementType::Entry->value,
                        ColdRoomMovementType::TransferIn->value,
                    ])
                    ->where('occurred_at', '<=', $occurredAt)
                    ->latest('occurred_at')
                    ->first();

                if ($lastEntry) {
                    $duration = max(0, $lastEntry->occurred_at->diffInMinutes($occurredAt));
                }
            }

            $traceEvent = null;
            if ($batch) {
                $traceType = $type->isInbound()
                    ? TraceabilityEventType::ColdStorageEntry
                    : ($type->isOutbound()
                        ? TraceabilityEventType::ColdStorageExit
                        : TraceabilityEventType::ColdStorageEntry);

                $traceEvent = $batch->traceabilityEvents()->create([
                    'type' => $traceType,
                    'organization_id' => $room->organization_id,
                    'location_id' => $room->location_id,
                    'actor_user_id' => $actor->id,
                    'occurred_at' => $occurredAt,
                    'quantity' => $data['quantity'],
                    'unit' => $data['unit'] ?? $batch->unit,
                    'title' => ($data['event_label'] ?? $type->label()).' — '.$room->name,
                    'meta' => [
                        'cold_room_id' => $room->id,
                        'temperature_c' => $data['temperature_c'] ?? null,
                        'humidity_pct' => $data['humidity_pct'] ?? null,
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
                'quantity' => $data['quantity'],
                'unit' => $data['unit'] ?? ($batch?->unit ?? 'kg'),
                'temperature_c' => $data['temperature_c'] ?? null,
                'humidity_pct' => $data['humidity_pct'] ?? null,
                'duration_minutes' => $duration,
                'event_label' => $data['event_label'] ?? $type->label(),
                'notes' => $data['notes'] ?? null,
                'traceability_event_id' => $traceEvent?->id,
            ]);

            $this->audit->log($actor, 'cold_room.movement_recorded', $movement, null, [
                'cold_room' => $room->code,
                'type' => $type->value,
                'batch' => $batch?->code,
            ]);

            return $movement->fresh([
                'coldRoom', 'batch', 'actor',
                'fromOrganization', 'toOrganization',
                'fromLocation', 'toLocation',
                'fromColdRoom', 'toColdRoom',
            ]);
        });
    }
}
