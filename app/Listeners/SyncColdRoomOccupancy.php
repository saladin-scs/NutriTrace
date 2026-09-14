<?php

namespace App\Listeners;

use App\Domain\ColdChain\OccupancyService;
use App\Events\StockMovementRecorded;
use App\Models\ColdRoom;

class SyncColdRoomOccupancy
{
    public function __construct(private OccupancyService $occupancy) {}

    public function handle(StockMovementRecorded $event): void
    {
        $roomId = $event->movement->cold_room_id;
        if (! $roomId) {
            return;
        }

        $room = ColdRoom::query()->find($roomId);
        if ($room) {
            $this->occupancy->recalculate($room);
        }
    }
}
