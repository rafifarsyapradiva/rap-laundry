<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Tunai = 'tunai';
    case TransferBank = 'transfer_bank';
    case QrisManual = 'qris_manual';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Tunai => 'Tunai',
            self::TransferBank => 'Transfer Bank',
            self::QrisManual => 'QRIS (Manual)',
            self::Lainnya => 'Lainnya',
        };
    }
}
