<?php

namespace App\Enums;

enum OrganizationType: string
{
    case Producer = 'producer';
    case Transformer = 'transformer';
    case Distributor = 'distributor';
    case Wholesaler = 'wholesaler';
    case Retailer = 'retailer';
    case Restaurant = 'restaurant';
    case Collector = 'collector';
    case Recycler = 'recycler';
    case Certifier = 'certifier';
    case PublicAuthority = 'public_authority';

    public function label(): string
    {
        return match ($this) {
            self::Producer => 'Producteur',
            self::Transformer => 'Transformateur',
            self::Distributor => 'Distributeur',
            self::Wholesaler => 'Grossiste',
            self::Retailer => 'Commerçant',
            self::Restaurant => 'Restaurant',
            self::Collector => 'Collecteur',
            self::Recycler => 'Recycleur',
            self::Certifier => 'Certificateur',
            self::PublicAuthority => 'Autorité publique',
        };
    }
}
