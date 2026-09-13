<?php

namespace App\Enums;

enum ColdRoomMovementType: string
{
    case Entry = 'entry';
    case Exit = 'exit';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case InventoryAdjust = 'inventory_adjust';
    case Loss = 'loss';

    public function label(): string
    {
        return match ($this) {
            self::Entry => 'Entrée',
            self::Exit => 'Sortie',
            self::TransferIn => 'Transfert entrant',
            self::TransferOut => 'Transfert sortant',
            self::InventoryAdjust => 'Ajustement inventaire',
            self::Loss => 'Perte / écart',
        };
    }

    public function isInbound(): bool
    {
        return in_array($this, [self::Entry, self::TransferIn], true);
    }

    public function isOutbound(): bool
    {
        return in_array($this, [self::Exit, self::TransferOut, self::Loss], true);
    }
}
