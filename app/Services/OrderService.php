<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\LaundryStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\InvalidStatusTransitionException;
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
        protected NumberSequenceService $sequenceService,
        protected PaymentService $paymentService
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
                'payment_status' => $totalAmount <= 0 ? PaymentStatus::Lunas->value : PaymentStatus::BelumDibayar->value,
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

            // 9. Jika terdapat pembayaran awal (uang muka/DP) opsional, catat secara atomik
            if (! empty($data['initial_payment']) && is_array($data['initial_payment'])) {
                $initAmount = (float) ($data['initial_payment']['amount'] ?? 0);
                if ($initAmount > 0) {
                    $this->paymentService->recordPayment($order, $data['initial_payment'], $operator);
                }
            }

            // 10. Rekam jejak audit aktivitas
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

            return $order->fresh(['customer', 'items', 'creator', 'statusHistories', 'validPayments']);
        });
    }

    /**
     * Perbarui status cucian pesanan dengan validasi transisi, pencatatan histori, dan jejak audit.
     *
     * @param Order $order Pesanan yang akan diubah statusnya
     * @param LaundryStatus $newStatus Status cucian target
     * @param User $operator Petugas yang melakukan pengubahan status
     * @param string|null $note Catatan operasional opsional
     * @param string|null $cancellationReason Alasan pembatalan (wajib jika status dibatalkan)
     * @return Order
     * @throws InvalidStatusTransitionException Jika transisi tidak diizinkan oleh aturan state machine
     * @throws DomainException Jika pesanan sudah berada pada status terminal atau alasan pembatalan kosong
     */
    public function updateLaundryStatus(
        Order $order,
        LaundryStatus $newStatus,
        User $operator,
        ?string $note = null,
        ?string $cancellationReason = null
    ): Order {
        if ($order->laundry_status === $newStatus) {
            return $order;
        }

        if ($order->isTerminal()) {
            throw new DomainException(
                "Pesanan {$order->order_number} sudah berada pada status '{$order->laundry_status->label()}' dan tidak dapat diubah lagi."
            );
        }

        if (! $order->canTransitionTo($newStatus)) {
            throw new InvalidStatusTransitionException(
                "Transisi status pesanan dari '{$order->laundry_status->label()}' ke '{$newStatus->label()}' tidak diizinkan."
            );
        }

        if ($newStatus === LaundryStatus::Dibatalkan) {
            if (empty(trim((string) $cancellationReason))) {
                throw new DomainException('Alasan pembatalan pesanan wajib diisi.');
            }

            // Keputusan Q3: Tolak pembatalan pesanan jika masih ada pembayaran valid
            if ($order->validPayments()->exists()) {
                throw new DomainException(
                    "Pesanan {$order->order_number} tidak dapat dibatalkan karena masih memiliki pembayaran berstatus valid. Lakukan pembatalan (void) seluruh pembayaran terlebih dahulu."
                );
            }
        }

        return DB::transaction(function () use ($order, $newStatus, $operator, $note, $cancellationReason): Order {
            $oldStatus = $order->laundry_status;

            // Re-check di dalam transaction lock
            if ($newStatus === LaundryStatus::Dibatalkan && $order->validPayments()->exists()) {
                throw new DomainException(
                    "Pesanan {$order->order_number} tidak dapat dibatalkan karena masih memiliki pembayaran berstatus valid. Lakukan pembatalan (void) seluruh pembayaran terlebih dahulu."
                );
            }

            $updates = [
                'laundry_status' => $newStatus->value,
            ];

            if ($newStatus === LaundryStatus::Selesai && ! $order->completed_at) {
                $updates['completed_at'] = now();
            }

            if ($newStatus === LaundryStatus::Dibatalkan) {
                $updates['cancelled_at'] = now();
                $updates['cancelled_by'] = $operator->id;
                $updates['cancellation_reason'] = trim((string) $cancellationReason);
            }

            $order->update($updates);

            // Simpan riwayat perubahan status
            $order->statusHistories()->create([
                'from_status' => $oldStatus->value,
                'to_status' => $newStatus->value,
                'changed_by' => $operator->id,
                'note' => ! empty($note) ? trim((string) $note) : null,
            ]);

            // Catat log aktivitas sistem
            $isCancelled = ($newStatus === LaundryStatus::Dibatalkan);
            ActivityLog::create([
                'user_id' => $operator->id,
                'action' => $isCancelled ? ActivityAction::OrderCancelled : ActivityAction::OrderStatusChanged,
                'subject_type' => Order::class,
                'subject_id' => $order->id,
                'description' => $isCancelled
                    ? "Pesanan {$order->order_number} dibatalkan oleh {$operator->name}. Alasan: {$cancellationReason}"
                    : "Status cucian pesanan {$order->order_number} diperbarui dari {$oldStatus->label()} menjadi {$newStatus->label()}.",
                'metadata' => [
                    'order_number' => $order->order_number,
                    'from_status' => $oldStatus->value,
                    'to_status' => $newStatus->value,
                    'actor_username' => $operator->username,
                    'note' => $note,
                    'cancellation_reason' => $cancellationReason,
                ],
            ]);

            return $order->fresh(['customer', 'items', 'creator', 'canceller', 'statusHistories.changer']);
        });
    }

    /**
     * Lakukan koreksi status cucian secara terkontrol oleh Administrator dengan alasan wajib.
     *
     * @param Order $order Pesanan yang statusnya akan dikoreksi
     * @param LaundryStatus $newStatus Status cucian target
     * @param User $admin Pengguna dengan wewenang Admin
     * @param string $reason Alasan koreksi status untuk jejak audit
     * @return Order
     * @throws DomainException Jika operator bukan admin, pesanan dibatalkan, atau target status tidak sah
     */
    public function correctLaundryStatus(
        Order $order,
        LaundryStatus $newStatus,
        User $admin,
        string $reason
    ): Order {
        if (! $admin->isAdmin()) {
            throw new DomainException('Hanya Administrator/Pemilik yang memiliki wewenang untuk melakukan koreksi status cucian.');
        }

        if ($order->laundry_status === LaundryStatus::Dibatalkan) {
            throw new DomainException('Pesanan yang telah dibatalkan tidak dapat dikoreksi kembali.');
        }

        if ($order->laundry_status === $newStatus) {
            throw new DomainException('Status target koreksi tidak boleh sama dengan status saat ini.');
        }

        if ($newStatus === LaundryStatus::Dibatalkan) {
            throw new DomainException('Untuk membatalkan pesanan, gunakan alur pembatalan resmi.');
        }

        $trimmedReason = trim($reason);
        if (strlen($trimmedReason) < 5) {
            throw new DomainException('Alasan koreksi status wajib diisi minimal 5 karakter.');
        }

        return DB::transaction(function () use ($order, $newStatus, $admin, $trimmedReason): Order {
            $oldStatus = $order->laundry_status;

            $updates = [
                'laundry_status' => $newStatus->value,
            ];

            // Jika koreksi mundur dari status Selesai, reset completed_at
            if ($oldStatus === LaundryStatus::Selesai && $newStatus !== LaundryStatus::Selesai) {
                $updates['completed_at'] = null;
            }

            // Jika koreksi ke Selesai, isi completed_at jika belum ada
            if ($newStatus === LaundryStatus::Selesai && ! $order->completed_at) {
                $updates['completed_at'] = now();
            }

            $order->update($updates);

            $formattedNote = "[KOREKSI] {$trimmedReason}";

            // Simpan riwayat perubahan status dengan catatan koreksi
            $order->statusHistories()->create([
                'from_status' => $oldStatus->value,
                'to_status' => $newStatus->value,
                'changed_by' => $admin->id,
                'note' => $formattedNote,
            ]);

            // Catat log aktivitas audit sistem
            ActivityLog::create([
                'user_id' => $admin->id,
                'action' => ActivityAction::OrderStatusChanged,
                'subject_type' => Order::class,
                'subject_id' => $order->id,
                'description' => "Status cucian pesanan {$order->order_number} dikoreksi oleh Admin {$admin->name} dari {$oldStatus->label()} menjadi {$newStatus->label()}. Alasan: {$trimmedReason}",
                'metadata' => [
                    'order_number' => $order->order_number,
                    'from_status' => $oldStatus->value,
                    'to_status' => $newStatus->value,
                    'is_correction' => true,
                    'correction_reason' => $trimmedReason,
                    'actor_username' => $admin->username,
                ],
            ]);

            return $order->fresh(['customer', 'items', 'creator', 'canceller', 'statusHistories.changer']);
        });
    }
}


