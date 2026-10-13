<?php

namespace App\Policies;

use App\Enums\LaundryStatus;
use App\Models\BusinessSetting;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat daftar pesanan.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Tentukan apakah pengguna dapat melihat detail pesanan tertentu.
     */
    public function view(User $user, Order $order): bool
    {
        return $user->is_active;
    }

    /**
     * Tentukan apakah pengguna dapat membuat pesanan laundry baru.
     * Kasir dan Admin aktif diizinkan.
     */
    public function create(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Tentukan apakah pengguna dapat memperbarui catatan pesanan.
     */
    public function update(User $user, Order $order): bool
    {
        return $user->is_active;
    }

    /**
     * Tentukan apakah pengguna dapat memperbarui status cucian pesanan.
     * Hanya pesanan non-terminal yang dapat diubah statusnya.
     */
    public function updateStatus(User $user, Order $order): bool
    {
        return $user->is_active && ! $order->isTerminal();
    }

    /**
     * Tentukan apakah pengguna dapat membatalkan pesanan.
     * Hanya pesanan non-terminal yang dapat dibatalkan.
     */
    public function cancel(User $user, Order $order): bool
    {
        return $user->is_active && ! $order->isTerminal();
    }

    /**
     * Tentukan apakah pengguna berhak melakukan koreksi status cucian (rewind/override).
     * Hanya Admin/Pemilik aktif yang diizinkan, dan pesanan belum dibatalkan permanen.
     */
    public function correctStatus(User $user, Order $order): bool
    {
        if (! $user->is_active || ! $user->isAdmin()) {
            return false;
        }

        // Pesanan yang dibatalkan tidak boleh dikoreksi
        return $order->laundry_status !== LaundryStatus::Dibatalkan;
    }



    /**
     * Tentukan apakah pengguna memiliki izin memberikan nominal diskon tertentu.
     *
     * @param User $user Pengguna yang sedang membuat order
     * @param float $discountAmount Nominal diskon yang diajukan
     * @param float $subtotal Total subtotal pesanan
     */
    public function applyDiscount(User $user, float $discountAmount, float $subtotal): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($discountAmount <= 0) {
            return true;
        }

        if ($discountAmount > $subtotal) {
            return false;
        }

        // Admin memiliki hak penuh memberikan diskon hingga subtotal
        if ($user->isAdmin()) {
            return true;
        }

        // Kasir dibatasi persentase maksimum dari pengaturan usaha (default 20%)
        if ($user->isKasir()) {
            $maxPercent = (float) BusinessSetting::get(BusinessSetting::KEY_CASHIER_MAX_DISCOUNT_PERCENT, '20');
            $maxAllowed = round(($subtotal * $maxPercent) / 100, 2);

            return $discountAmount <= $maxAllowed;
        }

        return false;
    }
}
