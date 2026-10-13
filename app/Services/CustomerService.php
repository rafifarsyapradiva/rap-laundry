<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\LaundryStatus;
use App\Enums\PaymentRecordStatus;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\User;
use Brick\Math\BigDecimal;
use DomainException;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function __construct(
        protected NumberSequenceService $sequenceService
    ) {}

    /**
     * Daftarkan pelanggan baru dengan kode otomatis CUST-XXXX dan pencatatan jejak audit.
     */
    public function createCustomer(array $data, User $actor): Customer
    {
        return DB::transaction(function () use ($data, $actor) {
            $customerCode = $this->sequenceService->generateCustomerCode();

            $customer = Customer::create([
                'customer_code' => $customerCode,
                'name' => trim($data['name']),
                'phone' => $this->normalizePhone($data['phone']),
                'address' => isset($data['address']) ? trim((string) $data['address']) : null,
                'notes' => isset($data['notes']) ? trim((string) $data['notes']) : null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->logActivity(
                actor: $actor,
                action: ActivityAction::CustomerCreated,
                customer: $customer,
                description: "Pelanggan baru {$customer->name} ({$customer->customer_code}) berhasil ditambahkan."
            );

            return $customer;
        });
    }

    /**
     * Perbarui data profil pelanggan yang sudah ada.
     */
    public function updateCustomer(Customer $customer, array $data, User $actor): Customer
    {
        return DB::transaction(function () use ($customer, $data, $actor) {
            $customer->update([
                'name' => trim($data['name']),
                'phone' => $this->normalizePhone($data['phone']),
                'address' => isset($data['address']) ? trim((string) $data['address']) : null,
                'notes' => isset($data['notes']) ? trim((string) $data['notes']) : null,
                'is_active' => (bool) ($data['is_active'] ?? $customer->is_active),
            ]);

            $this->logActivity(
                actor: $actor,
                action: ActivityAction::CustomerUpdated,
                customer: $customer,
                description: "Data pelanggan {$customer->name} ({$customer->customer_code}) berhasil diperbarui."
            );

            return $customer;
        });
    }

    /**
     * Arsipkan pelanggan (soft delete dan nonaktifkan).
     */
    public function archiveCustomer(Customer $customer, User $actor): void
    {
        DB::transaction(function () use ($customer, $actor) {
            $customer->update(['is_active' => false]);
            $customer->delete();

            $this->logActivity(
                actor: $actor,
                action: ActivityAction::CustomerArchived,
                customer: $customer,
                description: "Pelanggan {$customer->name} ({$customer->customer_code}) diarsipkan ke arsip nonaktif."
            );
        });
    }

    /**
     * Pulihkan pelanggan dari arsip (restore soft delete dan aktifkan kembali).
     */
    public function restoreCustomer(Customer $customer, User $actor): void
    {
        DB::transaction(function () use ($customer, $actor) {
            $customer->restore();
            $customer->update(['is_active' => true]);

            $this->logActivity(
                actor: $actor,
                action: ActivityAction::CustomerRestored,
                customer: $customer,
                description: "Pelanggan {$customer->name} ({$customer->customer_code}) dipulihkan dari arsip."
            );
        });
    }

    /**
     * Ubah status aktif/nonaktif pelanggan tanpa mengarsipkannya.
     */
    public function toggleStatus(Customer $customer, User $actor): bool
    {
        return DB::transaction(function () use ($customer, $actor) {
            $newStatus = ! $customer->is_active;
            $customer->update(['is_active' => $newStatus]);

            $statusText = $newStatus ? 'diaktifkan' : 'dinonaktifkan';

            $this->logActivity(
                actor: $actor,
                action: ActivityAction::CustomerStatusChanged,
                customer: $customer,
                description: "Status pelanggan {$customer->name} ({$customer->customer_code}) {$statusText}."
            );

            return $newStatus;
        });
    }

    /**
     * Hapus permanen pelanggan. Wajib ditolak jika memiliki riwayat transaksi!
     *
     * @throws \DomainException
     */
    public function forceDeleteCustomer(Customer $customer, User $actor): void
    {
        if ($customer->orders()->exists()) {
            throw new DomainException(
                "Pelanggan {$customer->name} ({$customer->customer_code}) tidak dapat dihapus permanen karena memiliki riwayat transaksi. Silakan gunakan fitur arsip/nonaktifkan."
            );
        }

        DB::transaction(function () use ($customer, $actor) {
            $customerName = $customer->name;
            $customerCode = $customer->customer_code;

            $customer->forceDelete();

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => ActivityAction::CustomerArchived,
                'subject_type' => Customer::class,
                'subject_id' => null,
                'description' => "Pelanggan {$customerName} ({$customerCode}) dihapus permanen (tanpa riwayat transaksi).",
                'metadata' => [
                    'actor_name' => $actor->name,
                ],
            ]);
        });
    }

    /**
     * Dapatkan ringkasan statistik metrik pelanggan untuk tampilan detail.
     */
    public function getCustomerMetrics(Customer $customer): array
    {
        $orders = $customer->orders()->with('validPayments')->get();

        $totalOrders = $orders->count();

        // Pesanan aktif: pesanan yang belum selesai diambil dan belum dibatalkan
        $activeOrders = $orders->filter(function ($order) {
            return ! in_array($order->laundry_status, [LaundryStatus::Selesai, LaundryStatus::Dibatalkan], true);
        })->count();

        $totalSpent = BigDecimal::zero();
        $outstandingBalance = BigDecimal::zero();

        foreach ($orders as $order) {
            if ($order->laundry_status === LaundryStatus::Dibatalkan) {
                continue;
            }

            $orderTotal = BigDecimal::of($order->total_amount);
            $totalSpent = $totalSpent->plus($orderTotal);

            $paidTotal = BigDecimal::zero();
            foreach ($order->validPayments as $payment) {
                $paidTotal = $paidTotal->plus(BigDecimal::of($payment->amount));
            }

            $unpaid = $orderTotal->minus($paidTotal);
            if ($unpaid->isGreaterThan(BigDecimal::zero())) {
                $outstandingBalance = $outstandingBalance->plus($unpaid);
            }
        }

        return [
            'total_orders' => $totalOrders,
            'active_orders' => $activeOrders,
            'total_spent' => (string) $totalSpent,
            'outstanding_balance' => (string) $outstandingBalance,
        ];
    }

    /**
     * Normalisasi format nomor telepon Indonesia (hapus spasi/strip, ubah +62 atau 62 menjadi 0).
     */
    public function normalizePhone(string $phone): string
    {
        $cleaned = preg_replace('/[\s\-\(\)\.]/', '', trim($phone));

        if (str_starts_with($cleaned, '+62')) {
            $cleaned = '0' . substr($cleaned, 3);
        } elseif (str_starts_with($cleaned, '62') && strlen($cleaned) >= 10) {
            $cleaned = '0' . substr($cleaned, 2);
        }

        return $cleaned;
    }

    /**
     * Rekam jejak audit aktivitas pelanggan.
     */
    protected function logActivity(User $actor, ActivityAction $action, Customer $customer, string $description): void
    {
        ActivityLog::create([
            'user_id' => $actor->id,
            'action' => $action,
            'subject_type' => Customer::class,
            'subject_id' => $customer->id,
            'description' => $description,
            'metadata' => [
                'customer_code' => $customer->customer_code,
                'customer_name' => $customer->name,
                'actor_username' => $actor->username,
            ],
        ]);
    }
}
