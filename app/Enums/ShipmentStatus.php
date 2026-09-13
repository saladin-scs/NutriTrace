<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case Draft = 'draft';
    case Dispatched = 'dispatched';
    case InTransit = 'in_transit';
    case Arrived = 'arrived';
    case Delivered = 'delivered';
    case Delayed = 'delayed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Dispatched => 'Expédié',
            self::InTransit => 'En transit',
            self::Arrived => 'Arrivé',
            self::Delivered => 'Livré',
            self::Delayed => 'Retardé',
            self::Cancelled => 'Annulé',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [
            self::Dispatched,
            self::InTransit,
            self::Arrived,
            self::Delayed,
        ], true);
    }
}
