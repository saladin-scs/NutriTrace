<?php

namespace App\Enums;

enum StockMovementType: string
{
    case StockIn = 'stock_in';
    case StockOut = 'stock_out';
    case Transfer = 'transfer';
    case Relocation = 'relocation';
    case Sale = 'sale';
    case Shipment = 'shipment';
    case Reception = 'reception';
    case Loss = 'loss';
    case Damage = 'damage';
    case Expiration = 'expiration';
    case Return = 'return';
    case Redistribution = 'redistribution';

    public function label(): string
    {
        return match ($this) {
            self::StockIn => 'Entrée stock',
            self::StockOut => 'Sortie stock',
            self::Transfer => 'Transfert',
            self::Relocation => 'Relocalisation',
            self::Sale => 'Vente',
            self::Shipment => 'Expédition',
            self::Reception => 'Réception',
            self::Loss => 'Perte',
            self::Damage => 'Dommage',
            self::Expiration => 'Expiration',
            self::Return => 'Retour',
            self::Redistribution => 'Redistribution',
        };
    }
}
