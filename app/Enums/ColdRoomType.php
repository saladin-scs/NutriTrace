<?php

namespace App\Enums;

enum ColdRoomType: string
{
    case Positive = 'positive';
    case Negative = 'negative';
    case Frozen = 'frozen';
    case Refrigerated = 'refrigerated';

    public function label(): string
    {
        return match ($this) {
            self::Positive => 'Positive',
            self::Negative => 'Négative',
            self::Frozen => 'Congélation',
            self::Refrigerated => 'Réfrigérée',
        };
    }
}
