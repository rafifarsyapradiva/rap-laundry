<?php

namespace App\Enums;

enum LaundryStatus: string
{
    case Diterima = 'diterima';
    case MenungguProses = 'menunggu_proses';
    case Dicuci = 'dicuci';
    case Dikeringkan = 'dikeringkan';
    case Disetrika = 'disetrika';
    case SiapDiambil = 'siap_diambil';
    case Selesai = 'selesai';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::Diterima => 'Diterima',
            self::MenungguProses => 'Menunggu Proses',
            self::Dicuci => 'Dicuci',
            self::Dikeringkan => 'Dikeringkan',
            self::Disetrika => 'Disetrika',
            self::SiapDiambil => 'Siap Diambil',
            self::Selesai => 'Selesai/Diambil',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    /**
     * Varian warna badge UI: neutral, info, warning, primary, success, danger.
     */
    public function color(): string
    {
        return match ($this) {
            self::Diterima => 'neutral',
            self::MenungguProses => 'info',
            self::Dicuci, self::Dikeringkan, self::Disetrika => 'warning',
            self::SiapDiambil => 'primary',
            self::Selesai => 'success',
            self::Dibatalkan => 'danger',
        };
    }

    /**
     * Maju boleh melompati tahap yang tidak relevan (mis. layanan "Setrika Saja"),
     * tetapi serah terima hanya sah setelah cucian benar-benar siap diambil.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Diterima => [
                self::MenungguProses, self::Dicuci, self::Dikeringkan,
                self::Disetrika, self::SiapDiambil, self::Dibatalkan,
            ],
            self::MenungguProses => [
                self::Dicuci, self::Dikeringkan, self::Disetrika,
                self::SiapDiambil, self::Dibatalkan,
            ],
            self::Dicuci => [self::Dikeringkan, self::Disetrika, self::SiapDiambil, self::Dibatalkan],
            self::Dikeringkan => [self::Disetrika, self::SiapDiambil, self::Dibatalkan],
            self::Disetrika => [self::SiapDiambil, self::Dibatalkan],
            self::SiapDiambil => [self::Selesai, self::Dibatalkan],
            self::Selesai, self::Dibatalkan => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return $this->allowedTransitions() === [];
    }
}
