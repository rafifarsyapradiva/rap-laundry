<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\LaundryStatus;
use App\Enums\PaymentStatus;
use App\Models\ActivityLog;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected NumberSequenceService $sequenceService
    ) {}

    /**
     * Buat dan simpan pesanan laundry baru beserta detail item dan histori status secara atomik.
     *
     * @param array<string, mixed> $data Data tervalidasi dari StoreOrderRequest
     * @param User $operator Petugas kasir / admin yang mencatat transaksi
     * @throws DomainException Jika data master tidak valid atau terjadi inkonsistensi bisnis
     */
    public function createOrder(array $data, User $operator): Order
    {
        return DB::transaction(function () use ($data, $operator): Order {
            // 1. Verifikasi keberadaan dan keaktifan pelanggan
            /** @var Customer $customer */
            $customer = Customer::query()->active()->findOrFail($data['customer_id']);

            // 2. Normalisasi waktu penerimaan dan estimasi selesai
            $receivedAt = Carbon::parse($data['received_at']);
            $estimatedCompletionAt = ! empty($data['estimated_completion_at'])
                ? Carbon::parse($data['estimated_completion_at'])
                : null;

            // 3. Muat dan kunci harga master layanan dari database (JANGAN PERCAYAI HARGA DARI CLIENT)
            $rawItems = $data['items'] ?? [];
            if (empty($rawItems)) {
                throw new DomainException('Pesanan wajib memiliki minimal satu item layanan.');
            }

            $serviceIds = collect($rawItems)->pluck('service_id')->unique()->toArray();
            $services = Service::query()->active()->whereIn('id', $serviceIds)->get()->keyBy('id');

            $serverSubtotal = 0.0;
            $preparedItems = [];

            foreach ($rawItems as $index => $item) {
                $serviceId = $item['service_id'] ?? null;
                if (! $serviceId || ! isset($services[$serviceId])) {
                    throw new DomainException("Layanan dengan ID #{$serviceId} tidak aktif atau tidak ditemukan di database.");
                }

                /** @var Service $service */
                $service = $services[$serviceId];

                $quantity = (float) ($item['quantity'] ?? 0);
                if ($quantity <= 0) {
                    throw new DomainException("Kuantitas untuk layanan '{$service->name}' harus lebih besar dari nol.");
                }

                // Ambil harga dari master layanan database (Snapshot Integrity)
                $unitPrice = (float) $service->price;
                $itemSubtotal = round($quantity * $unitPrice, 2);

                $serverSubtotal += $itemSubtotal;

                $preparedItems[] = [
                    'service_id' => $service->id,
                    'service_name_snapshot' => $service->name,
                    'billing_unit_snapshot' => $service->billing_unit,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $itemSubtotal,
                    'notes' => ! empty($item['notes']) ? trim((string) $item['notes']) : null,
                ];
            }

            // 4. Hitung diskon, biaya tambahan, dan grand total di server (DECIMAL)
            $rawDiscount = (float) ($data['discount_amount'] ?? 0);
            $discountAmount = max(0.0, min($rawDiscount, $serverSubtotal)); // Diskon tidak boleh melebihi subtotal

            $extraCharge = max(0.0, (float) ($data['extra_charge'] ?? 0));
            $totalAmount = max(0.0, round($serverSubtotal - $discountAmount + $extraCharge, 2));

            // 5. Buat nomor transaksi unik harian (anti-race condition dengan pessimistic locking)
            $prefix = BusinessSetting::get(BusinessSetting::KEY_ORDER_NUMBER_PREFIX, 'LD');
            $orderNumber = $this->sequenceService->generateOrderNumber(prefix: $prefix, date: $receivedAt);

            // 6. Simpan header transaksi pesanan
            /** @var Order $order */
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'created_by' => $operator->id,
                'received_at' => $receivedAt,
                'estimated_completion_at' => $estimatedCompletionAt,
                'completed_at' => null,
                'subtotal' => $serverSubtotal,
                'discount_amount' => $discountAmount,
                'extra_charge' => $extraCharge,
                'total_amount' => $totalAmount,
                'notes' => ! empty($data['notes']) ? trim((string) $data['notes']) : null,
                'laundry_status' => LaundryStatus::Diterima->value,
                'payment_status' => PaymentStatus::BelumDibayar->value,
            ]);

            // 7. Simpan detail item pesanan dengan snapshot
            $order->items()->createMany($preparedItems);

            // 8. Catat riwayat status awal pesanan (Diterima)
            $order->statusHistories()->create([
                'from_status' => null,
                'to_status' => LaundryStatus::Diterima->value,
                'changed_by' => $operator->id,
                'note' => 'Pesanan baru diterima dan dicatat ke sistem.',
            ]);

            // 9. Rekam jejak audit aktivitas
            ActivityLog::create([
                'user_id' => $operator->id,
                'action' => ActivityAction::OrderCreated,
                'subject_type' => Order::class,
                'subject_id' => $order->id,
                'description' => "Pesanan {$order->order_number} berhasil dibuat untuk pelanggan {$customer->name} (Total: Rp" . number_format($totalAmount, 0, ',', '.') . ").",
                'metadata' => [
                    'order_number' => $order->order_number,
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'items_count' => count($preparedItems),
                    'subtotal' => $serverSubtotal,
                    'discount_amount' => $discountAmount,
                    'extra_charge' => $extraCharge,
                    'total_amount' => $totalAmount,
                    'actor_username' => $operator->username,
                ],
            ]);

            return $order->load(['customer', 'items', 'creator', 'statusHistories']);
        });
    }
}
