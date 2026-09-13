<?php

namespace App\Enums;

enum DistributionNodeType: string
{
    case Producer = 'producer';
    case Transformer = 'transformer';
    case Warehouse = 'warehouse';
    case DistributionCenter = 'distribution_center';
    case ColdRoom = 'cold_room';
    case Distributor = 'distributor';
    case Wholesaler = 'wholesaler';
    case Retailer = 'retailer';
    case Restaurant = 'restaurant';
    case Hotel = 'hotel';
    case Collector = 'collector';

    public function label(): string
    {
        return match ($this) {
            self::Producer => 'Producteur',
            self::Transformer => 'Transformateur',
            self::Warehouse => 'Entrepôt',
            self::DistributionCenter => 'Centre de distribution',
            self::ColdRoom => 'Chambre froide',
            self::Distributor => 'Distributeur',
            self::Wholesaler => 'Grossiste',
            self::Retailer => 'Point de vente',
            self::Restaurant => 'Restaurant',
            self::Hotel => 'Hôtel',
            self::Collector => 'Collecteur',
        };
    }
}
