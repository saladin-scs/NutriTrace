<?php

namespace App\Domain\ColdChain;

use App\Enums\StorageRecordStatus;
use App\Models\ColdRoom;
use App\Models\StorageRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * First Expired, First Out — prioritise open storage by batch expiry.
 */
final class FefoService
{
    /**
     * @return Collection<int, StorageRecord>
     */
    public function priorityDispatchQueue(ColdRoom $room, ?float $neededKg = null): Collection
    {
        $records = StorageRecord::query()
            ->with(['batch.product'])
            ->where('cold_room_id', $room->id)
            ->whereIn('status', [
                StorageRecordStatus::Stored->value,
                StorageRecordStatus::Partial->value,
            ])
            ->where('remaining_quantity', '>', 0)
            ->get()
            ->sortBy(function (StorageRecord $record) {
                $expires = $record->batch?->expires_at;

                return $expires?->timestamp ?? PHP_INT_MAX;
            })
            ->values();

        if ($neededKg === null) {
            return $records;
        }

        $selected = collect();
        $remaining = $neededKg;

        foreach ($records as $record) {
            if ($remaining <= 0) {
                break;
            }
            $selected->push($record);
            $remaining -= (float) $record->remaining_quantity;
        }

        return $selected;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function nearExpiryWarnings(ColdRoom $room, int $withinDays = 3): array
    {
        $limit = now()->addDays($withinDays);

        return StorageRecord::query()
            ->with(['batch.product'])
            ->where('cold_room_id', $room->id)
            ->whereIn('status', [
                StorageRecordStatus::Stored->value,
                StorageRecordStatus::Partial->value,
            ])
            ->where('remaining_quantity', '>', 0)
            ->whereHas('batch', fn ($q) => $q->whereNotNull('expires_at')->where('expires_at', '<=', $limit))
            ->get()
            ->map(fn (StorageRecord $r) => [
                'storage_record_id' => $r->id,
                'batch_code' => $r->batch?->code,
                'product' => $r->batch?->product?->name,
                'remaining_quantity' => $r->remaining_quantity,
                'unit' => $r->unit,
                'expires_at' => $r->batch?->expires_at?->toIso8601String(),
                'days_left' => $r->batch?->expires_at
                    ? now()->diffInDays($r->batch->expires_at, false)
                    : null,
                'severity' => 'FEFO — sortie prioritaire recommandée',
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function excessiveStorageWarnings(ColdRoom $room, int $maxHours = 72): array
    {
        return StorageRecord::query()
            ->with(['batch.product'])
            ->where('cold_room_id', $room->id)
            ->whereIn('status', [
                StorageRecordStatus::Stored->value,
                StorageRecordStatus::Partial->value,
            ])
            ->where('remaining_quantity', '>', 0)
            ->where('entered_at', '<=', now()->subHours($maxHours))
            ->get()
            ->map(fn (StorageRecord $r) => [
                'storage_record_id' => $r->id,
                'batch_code' => $r->batch?->code,
                'product' => $r->batch?->product?->name,
                'remaining_quantity' => $r->remaining_quantity,
                'stored_for' => $r->storageDurationLabel(),
                'entered_at' => $r->entered_at?->toIso8601String(),
                'severity' => 'Durée de stockage excessive — vérification humaine recommandée',
            ])
            ->values()
            ->all();
    }
}
