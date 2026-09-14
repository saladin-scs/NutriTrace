<?php

namespace App\Domain\ColdChain;

use App\Enums\StorageRecordStatus;
use App\Models\ColdRoom;
use App\Models\StorageRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class OccupancyService
{
    public function recalculate(ColdRoom $room): ColdRoom
    {
        $occupied = (float) StorageRecord::query()
            ->where('cold_room_id', $room->id)
            ->whereIn('status', [
                StorageRecordStatus::Stored->value,
                StorageRecordStatus::Partial->value,
            ])
            ->sum('remaining_quantity');

        $room->update(['occupied_capacity_kg' => round($occupied, 3)]);

        return $room->fresh();
    }

    public function assertCapacity(ColdRoom $room, float $incomingQty): void
    {
        $available = $room->availableCapacityKg();
        if ($incomingQty > $available + 0.001) {
            throw new \InvalidArgumentException(sprintf(
                'Capacité insuffisante pour %s : disponible %.3f kg, demandé %.3f kg (occupation actuelle %.1f%%).',
                $room->code,
                $available,
                $incomingQty,
                $room->occupancyRate()
            ));
        }
    }

    /**
     * Forecast occupancy if a scheduled inbound quantity arrives.
     *
     * @return array{projected_kg: float, projected_rate: float, exceeds: bool}
     */
    public function projectInbound(ColdRoom $room, float $scheduledInboundKg): array
    {
        $projected = (float) $room->occupied_capacity_kg + $scheduledInboundKg;
        $capacity = (float) $room->capacity_kg;
        $rate = $capacity > 0 ? round(($projected / $capacity) * 100, 1) : 0.0;

        return [
            'projected_kg' => round($projected, 3),
            'projected_rate' => $rate,
            'exceeds' => $capacity > 0 && $projected > $capacity,
        ];
    }

    public function syncFromLedger(ColdRoom $room): void
    {
        DB::transaction(fn () => $this->recalculate($room));
    }
}
