<?php

namespace App\Enums;

enum OrganizationStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Suspended = 'suspended';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Verified => 'Vérifiée',
            self::Suspended => 'Suspendue',
            self::Rejected => 'Rejetée',
        };
    }
}
