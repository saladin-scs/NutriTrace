<?php

namespace App\Enums;

enum TraceabilityEventType: string
{
    case Production = 'production';
    case Collected = 'collected';
    case Receipt = 'receipt';
    case Transformation = 'transformation';
    case Distribution = 'distribution';
    case Stored = 'stored';
    case Moved = 'moved';
    case Inspected = 'inspected';
    case TemperatureChecked = 'temperature_checked';
    case Loaded = 'loaded';
    case Dispatched = 'dispatched';
    case InTransit = 'in_transit';
    case Arrived = 'arrived';
    case Delivered = 'delivered';
    case Rejected = 'rejected';
    case Damaged = 'damaged';
    case Lost = 'lost';
    case Expired = 'expired';
    case Redistributed = 'redistributed';
    case Sale = 'sale';
    case Consumption = 'consumption';
    case Loss = 'loss';
    case WasteDeclared = 'waste_declared';
    case Valorized = 'valorized';
    case ColdStorageEntry = 'cold_storage_entry';
    case ColdStorageExit = 'cold_storage_exit';

    public function label(): string
    {
        return match ($this) {
            self::Production => 'Produit',
            self::Collected => 'Collecté',
            self::Receipt => 'Réceptionné',
            self::Transformation => 'Transformé',
            self::Distribution => 'Distribué',
            self::Stored => 'Stocké',
            self::Moved => 'Déplacé',
            self::Inspected => 'Inspecté',
            self::TemperatureChecked => 'Température contrôlée',
            self::Loaded => 'Chargé',
            self::Dispatched => 'Expédié',
            self::InTransit => 'En transit',
            self::Arrived => 'Arrivé',
            self::Delivered => 'Livré',
            self::Rejected => 'Rejeté',
            self::Damaged => 'Endommagé',
            self::Lost => 'Perdu',
            self::Expired => 'Expiré',
            self::Redistributed => 'Redistribué',
            self::Sale => 'Vendu',
            self::Consumption => 'Consommé',
            self::Loss => 'Perte',
            self::WasteDeclared => 'Déchet déclaré',
            self::Valorized => 'Valorisé',
            self::ColdStorageEntry => 'Entrée chambre froide',
            self::ColdStorageExit => 'Sortie chambre froide',
        };
    }
}
