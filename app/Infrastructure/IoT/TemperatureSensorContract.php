<?php

namespace App\Infrastructure\IoT;

use App\Models\ColdRoom;

interface TemperatureSensorContract
{
    /**
     * Read current temperature for a cold room.
     * Implementations may call real IoT providers later.
     */
    public function read(ColdRoom $coldRoom): float;
}
