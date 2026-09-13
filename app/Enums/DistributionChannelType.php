<?php

namespace App\Enums;

enum DistributionChannelType: string
{
    case Direct = 'direct';
    case Short = 'short';
    case Long = 'long';
    case B2b = 'b2b';
    case B2c = 'b2c';
    case B2b2c = 'b2b2c';
    case Retail = 'retail';
    case Horeca = 'horeca';
    case CollectiveCatering = 'collective_catering';
    case Ecommerce = 'ecommerce';

    public function label(): string
    {
        return match ($this) {
            self::Direct => 'Direct',
            self::Short => 'Circuit court',
            self::Long => 'Circuit long',
            self::B2b => 'B2B',
            self::B2c => 'B2C',
            self::B2b2c => 'B2B2C',
            self::Retail => 'Retail',
            self::Horeca => 'HORECA',
            self::CollectiveCatering => 'Restauration collective',
            self::Ecommerce => 'E-commerce',
        };
    }
}
