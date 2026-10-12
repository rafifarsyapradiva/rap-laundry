<?php

namespace App\Enums;

enum PaymentRecordStatus: string
{
    case Valid = 'valid';
    case Voided = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::Valid => 'Valid',
            self::Voided => 'Dibatalkan (Void)',
        };
    }
}
