<?php

namespace App\Enums;

enum BillingUnit: string
{
    case Kg = 'kg';
    case Item = 'item';
    case Paket = 'package';

    public function label(): string
    {
        return match ($this) {
            self::Kg => 'Kg',
            self::Item => 'Item',
            self::Paket => 'Paket',
        };
    }

    /**
     * Hanya berat yang boleh pecahan (mis. 2,5 kg); item dan paket dihitung utuh.
     */
    public function allowsDecimal(): bool
    {
        return $this === self::Kg;
    }
}
