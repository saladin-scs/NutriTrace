<?php

namespace App\Facades;

use App\Domain\ColdChain\ColdChainManager;
use App\Models\Batch;
use App\Models\ColdRoom;
use App\Models\TemperatureRecord;
use App\Models\User;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array twin(ColdRoom $room)
 * @method static array fefoQueue(ColdRoom $room, ?float $neededKg = null)
 * @method static array massBalance(Batch $batch)
 * @method static TemperatureRecord recordSensorReading(ColdRoom $room, ?User $actor = null, ?float $override = null)
 * @method static ColdRoom recalculateOccupancy(ColdRoom $room)
 * @method static \App\Domain\ColdChain\OccupancyService occupancy()
 *
 * @see ColdChainManager
 */
class ColdChain extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ColdChainManager::class;
    }
}
