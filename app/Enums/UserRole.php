<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Consumer = 'consumer';
    case Producer = 'producer';
    case Transformer = 'transformer';
    case Distributor = 'distributor';
    case Retailer = 'retailer';
    case Restaurant = 'restaurant';
    case Collector = 'collector';
    case Certifier = 'certifier';

    /** @deprecated Kept for legacy nutrition seeders / rows */
    case User = 'user';

    public function isPlatformAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Consumer => 'Consommateur',
            self::Producer => 'Producteur',
            self::Transformer => 'Transformateur',
            self::Distributor => 'Distributeur',
            self::Retailer => 'Commerçant',
            self::Restaurant => 'Restaurant',
            self::Collector => 'Collecteur',
            self::Certifier => 'Organisme certificateur',
            self::User => 'Utilisateur',
        };
    }
}
