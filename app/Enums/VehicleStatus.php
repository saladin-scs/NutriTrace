<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case Available = 'available';
    case InTransit = 'in_transit';
    case Maintenance = 'maintenance';
    case Offline = 'offline';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::InTransit => 'En mission',
            self::Maintenance => 'Maintenance',
            self::Offline => 'Hors ligne',
        };
    }
}
