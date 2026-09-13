<?php

namespace App\Enums;

enum FuelType: string
{
    case Diesel = 'diesel';
    case Petrol = 'petrol';
    case Electric = 'electric';
    case Hybrid = 'hybrid';
    case Cng = 'cng';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Diesel => 'Diesel',
            self::Petrol => 'Essence',
            self::Electric => 'Électrique',
            self::Hybrid => 'Hybride',
            self::Cng => 'GNC',
            self::Other => 'Autre',
        };
    }
}
