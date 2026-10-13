<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\BillingUnit;
use App\Models\ActivityLog;
use App\Models\Service;
use App\Models\User;
use Brick\Math\BigDecimal;
use DomainException;
use Illuminate\Support\Facades\DB;

class LaundryServiceManagement
{
    /**
     * Daftarkan master layanan dan tarif baru ke dalam sistem.
     */
    public function createService(array $data, User $actor): Service
    {
        return DB::transaction(function () use ($data, $actor) {
            $price = BigDecimal::of($data['price'])->toScale(2);

            $billingUnit = $data['billing_unit'] instanceof BillingUnit
                ? $data['billing_unit']
                : BillingUnit::from($data['billing_unit']);

            $service = Service::create([
                'name' => trim((string) $data['name']),
                'description' => isset($data['description']) ? trim((string) $data['description']) : null,
                'billing_unit' => $billingUnit,
                'price' => (string) $price,
                'estimated_hours' => ! empty($data['estimated_hours']) ? (int) $data['estimated_hours'] : null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => ActivityAction::ServiceCreated,
                'subject_type' => Service::class,
                'subject_id' => $service->id,
                'description' => "Layanan baru '{$service->name}' berhasil ditambahkan dengan tarif {$service->formatted_price}.",
                'metadata' => [
                    'service_name' => $service->name,
                    'price' => (string) $price,
                    'billing_unit' => $service->billing_unit->value,
                    'actor_username' => $actor->username,
                ],
            ]);

            return $service;
        });
    }

    /**
     * Perbarui data master layanan.
     * Mendeteksi dan mencatat perubahan harga ke dalam audit log khusus.
     */
    public function updateService(Service $service, array $data, User $actor): Service
    {
        return DB::transaction(function () use ($service, $data, $actor) {
            $oldPrice = BigDecimal::of($service->price)->toScale(2);
            $newPrice = BigDecimal::of($data['price'])->toScale(2);

            $priceChanged = ! $oldPrice->isEqualTo($newPrice);

            $billingUnit = $data['billing_unit'] instanceof BillingUnit
                ? $data['billing_unit']
                : BillingUnit::from($data['billing_unit']);

            $service->update([
                'name' => trim((string) $data['name']),
                'description' => isset($data['description']) ? trim((string) $data['description']) : null,
                'billing_unit' => $billingUnit,
                'price' => (string) $newPrice,
                'estimated_hours' => ! empty($data['estimated_hours']) ? (int) $data['estimated_hours'] : null,
                'is_active' => (bool) ($data['is_active'] ?? $service->is_active),
            ]);

            // Jika harga berubah, catat ke log audit khusus perubahan harga
            if ($priceChanged) {
                $oldPriceFmt = number_format((float) (string) $oldPrice, 0, ',', '.');
                $newPriceFmt = number_format((float) (string) $newPrice, 0, ',', '.');

                ActivityLog::create([
                    'user_id' => $actor->id,
                    'action' => ActivityAction::ServicePriceChanged,
                    'subject_type' => Service::class,
                    'subject_id' => $service->id,
                    'description' => "Tarif layanan '{$service->name}' diubah dari Rp{$oldPriceFmt} menjadi Rp{$newPriceFmt}.",
                    'metadata' => [
                        'service_name' => $service->name,
                        'old_price' => (string) $oldPrice,
                        'new_price' => (string) $newPrice,
                        'actor_username' => $actor->username,
                    ],
                ]);
            }

            // Catat log pembaruan umum data layanan
            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => ActivityAction::ServiceUpdated,
                'subject_type' => Service::class,
                'subject_id' => $service->id,
                'description' => "Data layanan '{$service->name}' diperbarui.",
                'metadata' => [
                    'service_name' => $service->name,
                    'actor_username' => $actor->username,
                ],
            ]);

            return $service;
        });
    }

    /**
     * Arsipkan layanan (soft delete dan nonaktifkan).
     */
    public function archiveService(Service $service, User $actor): void
    {
        DB::transaction(function () use ($service, $actor) {
            $service->update(['is_active' => false]);
            $service->delete();

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => ActivityAction::ServiceArchived,
                'subject_type' => Service::class,
                'subject_id' => $service->id,
                'description' => "Layanan '{$service->name}' diarsipkan dan dinonaktifkan.",
                'metadata' => [
                    'service_name' => $service->name,
                    'actor_username' => $actor->username,
                ],
            ]);
        });
    }

    /**
     * Pulihkan layanan dari arsip.
     */
    public function restoreService(Service $service, User $actor): void
    {
        DB::transaction(function () use ($service, $actor) {
            $service->restore();
            $service->update(['is_active' => true]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => ActivityAction::ServiceRestored,
                'subject_type' => Service::class,
                'subject_id' => $service->id,
                'description' => "Layanan '{$service->name}' dipulihkan dari arsip dan diaktifkan kembali.",
                'metadata' => [
                    'service_name' => $service->name,
                    'actor_username' => $actor->username,
                ],
            ]);
        });
    }

    /**
     * Ubah status aktif/nonaktif layanan secara cepat.
     */
    public function toggleStatus(Service $service, User $actor): bool
    {
        return DB::transaction(function () use ($service, $actor) {
            $newStatus = ! $service->is_active;
            $service->update(['is_active' => $newStatus]);

            $statusText = $newStatus ? 'diaktifkan' : 'dinonaktifkan';

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => ActivityAction::ServiceStatusChanged,
                'subject_type' => Service::class,
                'subject_id' => $service->id,
                'description' => "Status layanan '{$service->name}' {$statusText}.",
                'metadata' => [
                    'service_name' => $service->name,
                    'is_active' => $newStatus,
                    'actor_username' => $actor->username,
                ],
            ]);

            return $newStatus;
        });
    }

    /**
     * Hapus permanen layanan. Wajib ditolak jika sudah memiliki riwayat pemakaian transaksi!
     *
     * @throws \DomainException
     */
    public function forceDeleteService(Service $service, User $actor): void
    {
        if ($service->orderItems()->exists()) {
            throw new DomainException(
                "Layanan '{$service->name}' tidak dapat dihapus permanen karena sudah pernah digunakan dalam transaksi cucian. Silakan gunakan fitur arsip/nonaktifkan."
            );
        }

        DB::transaction(function () use ($service, $actor) {
            $serviceName = $service->name;
            $service->forceDelete();

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => ActivityAction::ServiceArchived,
                'subject_type' => Service::class,
                'subject_id' => null,
                'description' => "Layanan '{$serviceName}' dihapus permanen (tanpa riwayat transaksi).",
                'metadata' => [
                    'service_name' => $serviceName,
                    'actor_username' => $actor->username,
                ],
            ]);
        });
    }

    /**
     * Hitung ringkasan statistik penggunaan layanan untuk tampilan detail.
     */
    public function getServiceMetrics(Service $service): array
    {
        $items = $service->orderItems()->with('order')->get();

        $usageCount = $items->count();

        $totalQuantity = BigDecimal::zero();
        $totalRevenue = BigDecimal::zero();

        foreach ($items as $item) {
            $totalQuantity = $totalQuantity->plus(BigDecimal::of($item->quantity));
            $totalRevenue = $totalRevenue->plus(BigDecimal::of($item->subtotal));
        }

        return [
            'usage_count' => $usageCount,
            'total_quantity' => (string) $totalQuantity,
            'total_revenue' => (string) $totalRevenue,
        ];
    }
}
