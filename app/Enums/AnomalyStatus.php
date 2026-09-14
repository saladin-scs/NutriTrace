<?php

namespace App\Enums;

enum AnomalyStatus: string
{
    case Open = 'open';
    case Acknowledged = 'acknowledged';
    case Investigating = 'investigating';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Ouverte',
            self::Acknowledged => 'Prise en compte',
            self::Investigating => 'En investigation',
            self::Resolved => 'Résolue',
            self::Dismissed => 'Écartée',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Open, self::Acknowledged, self::Investigating], true);
    }
}
