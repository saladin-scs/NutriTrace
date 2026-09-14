<?php

namespace App\Enums;

enum TemperatureStatus: string
{
    case Normal = 'normal';
    case Warning = 'warning';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Warning => 'Alerte',
            self::Critical => 'Critique',
        };
    }
}
