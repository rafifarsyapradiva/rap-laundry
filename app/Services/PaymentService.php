<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Models\ActivityLog;
use App\Models\BusinessSetting;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Catat pembayaran masuk secara atomik dengan database transaction dan locking.
     *
     * @param Order $order
     * @param array<string, mixed> $data
     * @param User $operator
     * @return Payment
     * @throws DomainException
     */
    public function recordPayment(Order $order, array $data, User $operator): Payment
    {
        return DB::transaction(function () use ($order, $data, $operator): Payment {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            // 1. Validasi status pesanan: pesanan dibatalkan tidak boleh menerima pembayaran
            if ($lockedOrder->is_cancelled) {
                throw new DomainException("Pesanan {$lockedOrder->order_number} telah dibatalkan dan tidak dapat menerima pembayaran.");
            }

            // 2. Hitung total pembayaran valid saat ini dan sisa tagihan via BigDecimal
            $remaining = $this->remainingBalance($lockedOrder);
            if ($remaining->isZero()) {
                throw new DomainException("Pesanan {$lockedOrder->order_number} sudah berstatus Lunas. Tidak ada sisa tagihan.");
            }

            // 3. Validasi nominal pembayaran
            if (empty($data['amount'])) {
                throw new DomainException('Nominal pembayaran wajib diisi.');
            }

            $amount = BigDecimal::of($data['amount']);
            if ($amount->isLessThanOrEqualTo(0)) {
                throw new DomainException('Nominal pembayaran harus lebih besar dari nol.');
            }

            if ($amount->isGreaterThan($remaining)) {
                $formattedAmount = 'Rp' . number_format($amount->toFloat(), 0, ',', '.');
                $formattedRemaining = 'Rp' . number_format($remaining->toFloat(), 0, ',', '.');
                throw new DomainException("Nominal pembayaran ({$formattedAmount}) melebihi sisa tagihan ({$formattedRemaining}).");
            }

            // 4. Validasi metode pembayaran
            $method = $data['method'] instanceof PaymentMethod
                ? $data['method']
                : PaymentMethod::from($data['method']);

            $this->validateEnabledMethod($method);

            // 5. Penanganan uang fisik diterima dan kembalian untuk metode Tunai
            $cashTendered = null;
            $changeAmount = null;

            if ($method === PaymentMethod::Tunai) {
                if (isset($data['cash_tendered']) && $data['cash_tendered'] !== '' && $data['cash_tendered'] !== null) {
                    $tendered = BigDecimal::of($data['cash_tendered']);
                    if ($tendered->isLessThan($amount)) {
                        $formattedTendered = 'Rp' . number_format($tendered->toFloat(), 0, ',', '.');
                        $formattedAmount = 'Rp' . number_format($amount->toFloat(), 0, ',', '.');
                        throw new DomainException("Uang tunai yang diserahkan ({$formattedTendered}) kurang dari nominal yang dibayarkan ({$formattedAmount}).");
                    }
                    $cashTendered = $tendered;
                    $changeAmount = $tendered->minus($amount);
                }
            }

            // 6. Validasi metode Lainnya: wajib ada catatan
            $notes = ! empty($data['notes']) ? trim((string) $data['notes']) : null;
            if ($method === PaymentMethod::Lainnya && empty($notes)) {
                throw new DomainException('Keterangan catatan wajib diisi untuk metode pembayaran Lainnya.');
            }

            // 7. Pengendalian tanggal pembayaran dan backdate
            $paymentDate = now();
            $isBackdated = false;

            if ($operator->isAdmin() && ! empty($data['payment_date'])) {
                $parsedDate = Carbon::parse($data['payment_date']);

                if ($parsedDate->isAfter(now())) {
                    throw new DomainException('Tanggal pembayaran tidak boleh di masa depan.');
                }

                if ($parsedDate->isBefore($lockedOrder->received_at)) {
                    $formattedReceived = $lockedOrder->received_at->format('d-m-Y H:i');
                    throw new DomainException("Tanggal pembayaran tidak boleh mendahului waktu masuk pesanan ({$formattedReceived} WIB).");
                }

                $paymentDate = $parsedDate;
                // Ditandai backdate jika tanggal bukan hari ini (atau selisih lebih dari 12 jam)
                $isBackdated = ! $parsedDate->isToday();

                if ($isBackdated && empty($notes)) {
                    throw new DomainException('Catatan wajib diisi saat mencatat pembayaran dengan tanggal lampau (backdate).');
                }
            }

            // 8. Simpan catatan pembayaran
            /** @var Payment $payment */
            $payment = Payment::create([
                'order_id' => $lockedOrder->id,
                'recorded_by' => $operator->id,
                'payment_date' => $paymentDate,
                'amount' => $amount->toScale(2),
                'method' => $method->value,
                'cash_tendered' => $cashTendered?->toScale(2),
                'change_amount' => $changeAmount?->toScale(2),
                'reference' => ! empty($data['reference']) ? trim((string) $data['reference']) : null,
                'notes' => $notes,
                'status' => PaymentRecordStatus::Valid->value,
            ]);

            // 9. Sinkronisasi status pembayaran pesanan
            $this->syncPaymentStatus($lockedOrder);

            // 10. Rekam jejak audit aktivitas sistem
            ActivityLog::create([
                'user_id' => $operator->id,
                'action' => ActivityAction::PaymentRecorded,
                'subject_type' => Payment::class,
                'subject_id' => $payment->id,
                'description' => "Pembayaran sebesar Rp" . number_format($amount->toFloat(), 0, ',', '.') . " dicatat untuk pesanan {$lockedOrder->order_number} via {$method->label()} oleh {$operator->name}.",
                'metadata' => [
                    'order_id' => $lockedOrder->id,
                    'order_number' => $lockedOrder->order_number,
                    'payment_id' => $payment->id,
                    'amount' => (string) $amount->toScale(2),
                    'method' => $method->value,
                    'cash_tendered' => $cashTendered ? (string) $cashTendered->toScale(2) : null,
                    'change_amount' => $changeAmount ? (string) $changeAmount->toScale(2) : null,
                    'payment_date' => $paymentDate->toIso8601String(),
                    'is_backdated' => $isBackdated,
                    'actor_username' => $operator->username,
                ],
            ]);

            return $payment->fresh(['order', 'recorder']);
        });
    }

    /**
     * Batalkan (void) pembayaran secara terkontrol oleh Administrator dengan alasan wajib.
     *
     * @param Payment $payment
     * @param User $admin
     * @param string $reason
     * @return Payment
     * @throws DomainException
     */
    public function voidPayment(Payment $payment, User $admin, string $reason): Payment
    {
        if (! $admin->isAdmin()) {
            throw new DomainException('Hanya Administrator/Pemilik yang memiliki wewenang untuk membatalkan (void) pembayaran.');
        }

        if ($payment->isVoided()) {
            throw new DomainException('Pembayaran ini sudah pernah dibatalkan (void) sebelumnya.');
        }

        $trimmedReason = trim($reason);
        if (strlen($trimmedReason) < 10) {
            throw new DomainException('Alasan pembatalan (void) pembayaran wajib diisi minimal 10 karakter.');
        }

        return DB::transaction(function () use ($payment, $admin, $trimmedReason): Payment {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $payment->order_id)->lockForUpdate()->firstOrFail();

            $payment->update([
                'status' => PaymentRecordStatus::Voided->value,
                'voided_by' => $admin->id,
                'voided_at' => now(),
                'void_reason' => $trimmedReason,
            ]);

            // Sinkronisasi ulang status pembayaran pesanan
            $this->syncPaymentStatus($lockedOrder);

            // Rekam jejak audit aktivitas sistem
            ActivityLog::create([
                'user_id' => $admin->id,
                'action' => ActivityAction::PaymentVoided,
                'subject_type' => Payment::class,
                'subject_id' => $payment->id,
                'description' => "Pembayaran #{$payment->id} (Rp" . number_format((float) $payment->amount, 0, ',', '.') . ") pada pesanan {$lockedOrder->order_number} dibatalkan (void) oleh Admin {$admin->name}. Alasan: {$trimmedReason}",
                'metadata' => [
                    'order_id' => $lockedOrder->id,
                    'order_number' => $lockedOrder->order_number,
                    'payment_id' => $payment->id,
                    'amount' => (string) $payment->amount,
                    'void_reason' => $trimmedReason,
                    'actor_username' => $admin->username,
                ],
            ]);

            return $payment->fresh(['order', 'recorder', 'voider']);
        });
    }

    /**
     * Hitung total nominal pembayaran yang sah (valid) untuk pesanan tertentu.
     */
    public function totalPaid(Order $order): BigDecimal
    {
        $sum = Payment::query()
            ->where('order_id', $order->id)
            ->where('status', PaymentRecordStatus::Valid->value)
            ->sum('amount');

        return BigDecimal::of($sum ?? '0');
    }

    /**
     * Hitung sisa saldo tagihan yang belum lunas via BigDecimal.
     */
    public function remainingBalance(Order $order): BigDecimal
    {
        $total = BigDecimal::of($order->total_amount);
        $paid = $this->totalPaid($order);
        $diff = $total->minus($paid);

        return $diff->isNegative() ? BigDecimal::zero() : $diff;
    }

    /**
     * Sinkronisasi status pembayaran pada record pesanan.
     */
    public function syncPaymentStatus(Order $order): PaymentStatus
    {
        $total = BigDecimal::of($order->total_amount);
        $paid = $this->totalPaid($order);

        $newStatus = PaymentStatus::fromAmounts($total, $paid);
        $order->update(['payment_status' => $newStatus->value]);

        return $newStatus;
    }

    /**
     * Hitung akumulasi uang kas masuk valid dalam rentang tanggal pembayaran.
     * Digunakan untuk pengujian Gate 8 dan laporan kas masuk harian/bulanan.
     */
    public function cashReceivedBetween(Carbon|string $from, Carbon|string $to, ?PaymentMethod $method = null): BigDecimal
    {
        $query = Payment::query()
            ->where('status', PaymentRecordStatus::Valid->value)
            ->whereBetween('payment_date', [$from, $to]);

        if ($method !== null) {
            $query->where('method', $method->value);
        }

        $sum = $query->sum('amount');

        return BigDecimal::of($sum ?? '0')->toScale(2);
    }

    /**
     * Validasi apakah metode pembayaran yang dipilih diaktifkan pada pengaturan usaha.
     */
    protected function validateEnabledMethod(PaymentMethod $method): void
    {
        $enabledJson = BusinessSetting::get('enabled_payment_methods');
        if (empty($enabledJson)) {
            return; // Default: seluruh metode aktif
        }

        $enabledMethods = json_decode($enabledJson, true);
        if (is_array($enabledMethods) && ! in_array($method->value, $enabledMethods, true)) {
            throw new DomainException("Metode pembayaran '{$method->label()}' sedang dinonaktifkan oleh pengaturan sistem.");
        }
    }
}
