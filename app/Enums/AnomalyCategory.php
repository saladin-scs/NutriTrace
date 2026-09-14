<?php

namespace App\Enums;

enum AnomalyCategory: string
{
    case DeliveryDelay = 'delivery_delay';
    case TemperatureBreach = 'temperature_breach';
    case CapacityAnomaly = 'capacity_anomaly';
    case UnusualStorageDuration = 'unusual_storage_duration';
    case NearExpiry = 'near_expiry';
    case MassBalance = 'mass_balance';
    case QuantityDiscrepancy = 'quantity_discrepancy';
    case StockConcentration = 'stock_concentration';
    case ExcessiveLoss = 'excessive_loss';
    case RegionalSupplyDrop = 'regional_supply_drop';
    case UnusualStockMovement = 'unusual_stock_movement';
    case TraceabilityGap = 'traceability_gap';

    public function label(): string
    {
        return match ($this) {
            self::DeliveryDelay => 'Retard de livraison',
            self::TemperatureBreach => 'Dépassement de température',
            self::CapacityAnomaly => 'Anomalie de capacité',
            self::UnusualStorageDuration => 'Durée de stockage inhabituelle',
            self::NearExpiry => 'Proche péremption',
            self::MassBalance => 'Écart de masse',
            self::QuantityDiscrepancy => 'Écart de quantité',
            self::StockConcentration => 'Concentration de stock',
            self::ExcessiveLoss => 'Pertes excessives',
            self::RegionalSupplyDrop => 'Baisse d’offre régionale',
            self::UnusualStockMovement => 'Mouvement de stock inhabituel',
            self::TraceabilityGap => 'Lacune de traçabilité',
        };
    }
}
