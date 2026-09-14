<?php

namespace App\Enums;

enum AnomalySeverity: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Info => 'Info',
            self::Warning => 'Alerte',
            self::Critical => 'Critique',
        };
    }

    public function weight(): int
    {
        return match ($this) {
            self::Info => 1,
            self::Warning => 3,
            self::Critical => 5,
        };
    }
}
