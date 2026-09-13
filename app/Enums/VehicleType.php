<?php

namespace App\Enums;

enum VehicleType: string
{
    case Van = 'van';
    case Truck = 'truck';
    case RefrigeratedTruck = 'refrigerated_truck';
    case Pickup = 'pickup';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Van => 'Fourgon',
            self::Truck => 'Camion',
            self::RefrigeratedTruck => 'Camion frigorifique',
            self::Pickup => 'Pick-up',
            self::Other => 'Autre',
        };
    }
}
