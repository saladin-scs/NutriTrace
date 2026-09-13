<?php

namespace App\Enums;

enum RouteStatus: string
{
    case Planned = 'planned';
    case Active = 'active';
    case Delayed = 'delayed';
    case Critical = 'critical';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planifiée',
            self::Active => 'Active',
            self::Delayed => 'Retardée',
            self::Critical => 'Critique',
            self::Completed => 'Terminée',
            self::Cancelled => 'Annulée',
        };
    }
}
