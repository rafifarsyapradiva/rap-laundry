<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat daftar pembayaran.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Tentukan apakah pengguna dapat melihat detail pembayaran.
     */
    public function view(User $user, Payment $payment): bool
    {
        return $user->is_active;
    }

    /**
     * Tentukan apakah pengguna berwenang mencatat pembayaran untuk pesanan tertentu.
     * Kasir dan Admin aktif diizinkan, asalkan pesanan tidak dibatalkan dan belum lunas.
     */
    public function create(User $user, Order $order): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($order->is_cancelled) {
            return false;
        }

        return $order->remaining_balance > 0;
    }

    /**
     * Tentukan apakah pengguna berwenang membatalkan (void) pembayaran yang sudah tercatat.
     * Khusus Administrator / Pemilik aktif, dan pembayaran tersebut masih berstatus valid.
     */
    public function void(User $user, Payment $payment): bool
    {
        if (! $user->is_active || ! $user->isAdmin()) {
            return false;
        }

        return $payment->isValid();
    }
}
