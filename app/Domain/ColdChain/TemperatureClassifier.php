<?php

namespace App\Domain\ColdChain;

use App\Enums\TemperatureStatus;
use App\Models\ColdRoom;

final class TemperatureClassifier
{
    public function classify(ColdRoom $room, float $temperature): TemperatureStatus
    {
        $min = $room->target_temp_min_c !== null ? (float) $room->target_temp_min_c : null;
        $max = $room->target_temp_max_c !== null ? (float) $room->target_temp_max_c : null;

        if ($min === null && $max === null) {
            return TemperatureStatus::Normal;
        }

        $span = max(0.5, (($max ?? $min) - ($min ?? $max)));
        $warnBand = $span * 0.15;

        if ($min !== null && $temperature < $min) {
            return $temperature < ($min - $warnBand)
                ? TemperatureStatus::Critical
                : TemperatureStatus::Warning;
        }

        if ($max !== null && $temperature > $max) {
            return $temperature > ($max + $warnBand)
                ? TemperatureStatus::Critical
                : TemperatureStatus::Warning;
        }

        return TemperatureStatus::Normal;
    }
}
