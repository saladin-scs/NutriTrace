<?php

namespace App\Infrastructure\IoT;

use App\Models\ColdRoom;

/**
 * Deterministic simulated sensor — ready to swap for a real provider.
 */
final class SimulatedTemperatureSensor implements TemperatureSensorContract
{
    public function read(ColdRoom $coldRoom): float
    {
        $min = (float) ($coldRoom->target_temp_min_c ?? 2);
        $max = (float) ($coldRoom->target_temp_max_c ?? 6);
        $mid = ($min + $max) / 2;

        // Slight drift based on room id + minute for demo realism without hard-coding values in UI.
        $drift = sin(($coldRoom->id * 17) + (now()->minute / 10)) * (($max - $min) * 0.35);

        return round($mid + $drift, 2);
    }
}
