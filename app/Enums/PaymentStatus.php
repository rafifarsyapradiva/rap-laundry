<?php

namespace App\Enums;

use Brick\Math\BigDecimal;

enum PaymentStatus: string
{
    case BelumDibayar = 'belum_dibayar';
    case DibayarSebagian = 'dibayar_sebagian';
    case Lunas = 'lunas';

    public function label(): string
    {
        return match ($this) {
            self::BelumDibayar => 'Belum Dibayar',
            self::DibayarSebagian => 'Dibayar Sebagian',
            self::Lunas => 'Lunas',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BelumDibayar => 'danger',
            self::DibayarSebagian => 'warning',
            self::Lunas => 'success',
        };
    }

    /**
     * Status diturunkan dari total tagihan dan jumlah pembayaran valid.
     * Lunas diperiksa lebih dulu agar pesanan bertotal Rp0 (diskon penuh) tidak dianggap belum dibayar.
     */
    public static function fromAmounts(BigDecimal|string|int $totalAmount, BigDecimal|string|int $paidAmount): self
    {
        $total = BigDecimal::of($totalAmount);
        $paid = BigDecimal::of($paidAmount);

        if ($paid->isGreaterThanOrEqualTo($total)) {
            return self::Lunas;
        }

        if ($paid->isZero()) {
            return self::BelumDibayar;
        }

        return self::DibayarSebagian;
    }
}
