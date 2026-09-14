<?php

namespace App\Enums;

enum StorageRecordStatus: string
{
    case Stored = 'stored';
    case Released = 'released';
    case Partial = 'partial';
    case Lost = 'lost';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Stored => 'En stock',
            self::Released => 'Sorti',
            self::Partial => 'Sortie partielle',
            self::Lost => 'Perdu',
            self::Expired => 'Expiré',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Stored, self::Partial], true);
    }
}
