<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Enums\BillingUnit;
use App\Enums\LaundryStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Service;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian Komprehensif Task 7.1 — Transisi Status Cucian
 *
 * Memverifikasi:
 * 1. Implementasi 8 status lengkap (Diterima -> Menunggu -> Dicuci -> Dikeringkan -> Disetrika -> Siap Diambil -> Selesai / Dibatalkan).
 * 2. Layanan tanpa setrika (Cuci Kering) tidak wajib melewati tahap Disetrika (bypassed).
 * 3. Layanan tanpa cuci (Setrika Saja) tidak wajib melewati tahap Dicuci & Dikeringkan (bypassed).
 * 4. Penolakan transisi mundur atau loncat ilegal di sisi server.
 * 5. Pembatalan pesanan dari status aktif dengan pencatatan alasan dan aktor.
 * 6. Penolakan pembatalan tanpa alasan.
 * 7. Proteksi status terminal (Selesai dan Dibatalkan tidak dapat diubah lagi).
 * 8. Otorisasi Kasir & Admin vs Tamu / User Nonaktif.
 * 9. Independensi status fisik cucian terhadap status pembayaran (Gate 7).
 * 10. Pencatatan kronologis histori status dan catatan operasional.
 * 11. Render panel kontrol status dan stepper pada Blade view.
 */
class OrderStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected User $kasir;
    protected User $admin;
    protected User $inactiveUser;
    protected Customer $customer;
    protected Service $serviceCuciSetrika;
    protected Service $serviceCuciKering;
    protected Service $serviceSetrikaSaja;
    protected OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kasir = User::factory()->create([
            'name' => 'Budi Kasir',
            'username' => 'kasir1',
            'role' => UserRole::Kasir,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Agus Admin',
            'username' => 'admin1',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->inactiveUser = User::factory()->create([
            'name' => 'Staf Nonaktif',
            'username' => 'staf_nonaktif',
            'role' => UserRole::Kasir,
            'is_active' => false,
        ]);

        $this->customer = Customer::factory()->create([
            'name' => 'Dewi Lestari',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        $this->serviceCuciSetrika = Service::factory()->create([
            'name' => 'Cuci Setrika',
            'billing_unit' => BillingUnit::Kg,
            'price' => 8000,
            'estimated_hours' => 48,
            'is_active' => true,
        ]);

        $this->serviceCuciKering = Service::factory()->create([
            'name' => 'Cuci Kering',
            'billing_unit' => BillingUnit::Kg,
            'price' => 6000,
            'estimated_hours' => 24,
            'is_active' => true,
        ]);

        $this->serviceSetrikaSaja = Service::factory()->create([
            'name' => 'Setrika Saja',
            'billing_unit' => BillingUnit::Kg,
            'price' => 5000,
            'estimated_hours' => 24,
            'is_active' => true,
        ]);

        $this->orderService = app(OrderService::class);
    }

    /**
     * Helper untuk membuat pesanan dengan layanan tertentu.
     */
    protected function createTestOrder(Service $service, float $qty = 3.0, array $orderOverrides = []): Order
    {
        $order = Order::create(array_merge([
            'order_number' => 'LD-' . now()->format('Ymd') . '-000' . rand(1, 999),
            'customer_id' => $this->customer->id,
            'created_by' => $this->kasir->id,
            'received_at' => now(),
            'estimated_completion_at' => now()->addHours(48),
            'subtotal' => $service->price * $qty,
            'discount_amount' => 0,
            'extra_charge' => 0,
            'total_amount' => $service->price * $qty,
            'laundry_status' => LaundryStatus::Diterima->value,
            'payment_status' => PaymentStatus::BelumDibayar->value,
        ], $orderOverrides));

        OrderItem::create([
            'order_id' => $order->id,
            'service_id' => $service->id,
            'service_name_snapshot' => $service->name,
            'billing_unit_snapshot' => $service->billing_unit->value,
            'quantity' => $qty,
            'unit_price' => $service->price,
            'subtotal' => $service->price * $qty,
        ]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => null,
            'to_status' => LaundryStatus::Diterima->value,
            'changed_by' => $this->kasir->id,
            'note' => 'Pesanan baru diterima.',
        ]);

        return $order->fresh(['items', 'statusHistories']);
    }

    /**
     * Uji 1: Siklus transisi status lengkap untuk layanan Cuci + Setrika.
     */
    public function test_full_linear_status_transitions_with_ironing_service(): void
    {
        $order = $this->createTestOrder($this->serviceCuciSetrika);

        $this->assertTrue($order->requiresWashing());
        $this->assertTrue($order->requiresIroning());
        $this->assertEquals(LaundryStatus::Diterima, $order->laundry_status);

        $steps = [
            LaundryStatus::MenungguProses,
            LaundryStatus::Dicuci,
            LaundryStatus::Dikeringkan,
            LaundryStatus::Disetrika,
            LaundryStatus::SiapDiambil,
            LaundryStatus::Selesai,
        ];

        foreach ($steps as $targetStatus) {
            $response = $this->actingAs($this->kasir)
                ->patch(route('orders.update-status', $order), [
                    'status' => $targetStatus->value,
                    'note' => "Proses menuju {$targetStatus->label()}",
                ]);

            $response->assertRedirect(route('orders.show', $order));
            $response->assertSessionHas('success');

            $order->refresh();
            $this->assertEquals($targetStatus, $order->laundry_status);
        }

        // Pada status Selesai, completed_at wajib terisi
        $this->assertNotNull($order->completed_at);

        // Histori status harus mencatat 7 entri (1 awal + 6 transisi)
        $this->assertCount(7, $order->statusHistories);

        // Verifikasi log aktivitas tercatat
        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $order->id,
            'action' => ActivityAction::OrderStatusChanged->value,
        ]);
    }

    /**
     * Uji 2: Layanan "Cuci Kering" melewati/melompati tahap Disetrika.
     */
    public function test_cuci_kering_service_skips_disetrika_stage(): void
    {
        $order = $this->createTestOrder($this->serviceCuciKering);

        $this->assertTrue($order->requiresWashing());
        $this->assertFalse($order->requiresIroning());

        // Jalankan sampai Dikeringkan
        $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::MenungguProses->value,
        ]);
        $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::Dicuci->value,
        ]);
        $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::Dikeringkan->value,
        ]);

        $order->refresh();
        $this->assertEquals(LaundryStatus::Dikeringkan, $order->laundry_status);

        // Rekomendasi status berikutnya langsung Siap Diambil (bukan Disetrika)
        $this->assertEquals(LaundryStatus::SiapDiambil, $order->getNextRecommendedStatus());

        // Transisi dari Dikeringkan langsung ke Siap Diambil berhasil
        $response = $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::SiapDiambil->value,
            'note' => 'Cucian kering selesai dan dipacking tanpa setrika.',
        ]);

        $response->assertRedirect(route('orders.show', $order));
        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(LaundryStatus::SiapDiambil, $order->laundry_status);
    }

    /**
     * Uji 3: Layanan "Setrika Saja" melompati tahap Dicuci dan Dikeringkan.
     */
    public function test_setrika_saja_service_skips_dicuci_and_dikeringkan_stages(): void
    {
        $order = $this->createTestOrder($this->serviceSetrikaSaja);

        $this->assertFalse($order->requiresWashing());
        $this->assertTrue($order->requiresIroning());

        // Masuk ke Menunggu Proses
        $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::MenungguProses->value,
        ]);

        $order->refresh();
        $this->assertEquals(LaundryStatus::MenungguProses, $order->laundry_status);

        // Rekomendasi status berikutnya langsung Disetrika (bukan Dicuci)
        $this->assertEquals(LaundryStatus::Disetrika, $order->getNextRecommendedStatus());

        // Transisi dari Menunggu Proses langsung ke Disetrika berhasil
        $response = $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::Disetrika->value,
            'note' => 'Pakaian langsung disetrika tanpa tahap pencucian.',
        ]);

        $response->assertRedirect(route('orders.show', $order));
        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(LaundryStatus::Disetrika, $order->laundry_status);
    }

    /**
     * Uji 4: Penolakan transisi mundur di server (contoh: dari Dicuci kembali ke Diterima).
     */
    public function test_server_rejects_invalid_backwards_transitions(): void
    {
        $order = $this->createTestOrder($this->serviceCuciSetrika, 2.0, [
            'laundry_status' => LaundryStatus::Dicuci->value,
        ]);

        // Coba memundurkan ke Diterima
        $response = $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::Diterima->value,
        ]);

        $response->assertRedirect(route('orders.show', $order));
        $response->assertSessionHasErrors('status');

        $order->refresh();
        $this->assertEquals(LaundryStatus::Dicuci, $order->laundry_status);

        // Coba memundurkan ke Menunggu Proses
        $response2 = $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::MenungguProses->value,
        ]);

        $response2->assertRedirect(route('orders.show', $order));
        $response2->assertSessionHasErrors('status');

        $order->refresh();
        $this->assertEquals(LaundryStatus::Dicuci, $order->laundry_status);
    }

    /**
     * Uji 5: Penolakan loncat langsung ke Selesai dari status awal Diterima.
     */
    public function test_server_rejects_jumping_directly_to_selesai_from_diterima(): void
    {
        $order = $this->createTestOrder($this->serviceCuciSetrika);

        $response = $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::Selesai->value,
        ]);

        $response->assertRedirect(route('orders.show', $order));
        $response->assertSessionHasErrors('status');

        $order->refresh();
        $this->assertEquals(LaundryStatus::Diterima, $order->laundry_status);
        $this->assertNull($order->completed_at);
    }

    /**
     * Uji 6: Pembatalan pesanan dari status aktif disertai alasan pembatalan dan pencatatan aktor.
     */
    public function test_order_cancellation_with_reason_from_various_active_statuses(): void
    {
        $order = $this->createTestOrder($this->serviceCuciSetrika, 2.0, [
            'laundry_status' => LaundryStatus::Dicuci->value,
        ]);

        $cancellationReason = 'Pelanggan membatalkan cucian karena pakaian darurat harus dibawa bepergian.';

        $response = $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::Dibatalkan->value,
            'cancellation_reason' => $cancellationReason,
            'note' => 'Pakaian dikeluarkan dari mesin cuci.',
        ]);

        $response->assertRedirect(route('orders.show', $order));
        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(LaundryStatus::Dibatalkan, $order->laundry_status);
        $this->assertTrue($order->is_cancelled);
        $this->assertNotNull($order->cancelled_at);
        $this->assertEquals($this->kasir->id, $order->cancelled_by);
        $this->assertEquals($cancellationReason, $order->cancellation_reason);

        // Riwayat status bertambah
        $latestHistory = $order->latestStatusHistory;
        $this->assertEquals(LaundryStatus::Dibatalkan, $latestHistory->to_status);
        $this->assertEquals(LaundryStatus::Dicuci, $latestHistory->from_status);
        $this->assertEquals($this->kasir->id, $latestHistory->changed_by);

        // Activity log tercatat
        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $order->id,
            'action' => ActivityAction::OrderCancelled->value,
        ]);
    }

    /**
     * Uji 7: Pembatalan ditolak jika alasan pembatalan kosong.
     */
    public function test_cancellation_requires_cancellation_reason(): void
    {
        $order = $this->createTestOrder($this->serviceCuciSetrika);

        $response = $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::Dibatalkan->value,
            'cancellation_reason' => '',
        ]);

        $response->assertSessionHasErrors('cancellation_reason');

        $order->refresh();
        $this->assertEquals(LaundryStatus::Diterima, $order->laundry_status);
        $this->assertFalse($order->is_cancelled);
    }

    /**
     * Uji 8: Status terminal (Selesai dan Dibatalkan) tidak dapat dimutasi lagi.
     */
    public function test_terminal_status_cannot_be_mutated(): void
    {
        // 1. Pesanan Selesai
        $completedOrder = $this->createTestOrder($this->serviceCuciSetrika, 1.0, [
            'laundry_status' => LaundryStatus::Selesai->value,
            'completed_at' => now(),
        ]);

        $responseCompleted = $this->actingAs($this->kasir)->patch(route('orders.update-status', $completedOrder), [
            'status' => LaundryStatus::Dicuci->value,
        ]);

        // Ditolak oleh Policy (403 Forbidden)
        $responseCompleted->assertStatus(403);
        $this->assertEquals(LaundryStatus::Selesai, $completedOrder->fresh()->laundry_status);

        // 2. Pesanan Dibatalkan
        $cancelledOrder = $this->createTestOrder($this->serviceCuciSetrika, 1.0, [
            'laundry_status' => LaundryStatus::Dibatalkan->value,
            'cancelled_at' => now(),
            'cancelled_by' => $this->kasir->id,
            'cancellation_reason' => 'Sudah dibatalkan sebelumnya.',
        ]);

        $responseCancelled = $this->actingAs($this->kasir)->patch(route('orders.update-status', $cancelledOrder), [
            'status' => LaundryStatus::MenungguProses->value,
        ]);

        $responseCancelled->assertStatus(403);
        $this->assertEquals(LaundryStatus::Dibatalkan, $cancelledOrder->fresh()->laundry_status);
    }

    /**
     * Uji 9: Pengguna belum login dan staf nonaktif ditolak mengubah status.
     */
    public function test_unauthenticated_and_inactive_users_cannot_update_status(): void
    {
        $order = $this->createTestOrder($this->serviceCuciSetrika);

        // Tamu (belum login) dialihkan ke login
        $guestResponse = $this->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::MenungguProses->value,
        ]);
        $guestResponse->assertRedirect(route('login'));

        // Pengguna nonaktif ditolak dan dialihkan ke login dengan notifikasi
        $inactiveResponse = $this->actingAs($this->inactiveUser)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::MenungguProses->value,
        ]);
        $inactiveResponse->assertRedirect(route('login'));
        $inactiveResponse->assertSessionHasErrors('identity');

        $order->refresh();
        $this->assertEquals(LaundryStatus::Diterima, $order->laundry_status);
    }

    /**
     * Uji 10: Status cucian beroperasi independen dari status pembayaran (Kepatuhan Gate 7).
     */
    public function test_laundry_status_operates_independently_from_payment_status(): void
    {
        // Pesanan belum bayar sama sekali
        $orderUnpaid = $this->createTestOrder($this->serviceCuciKering, 2.0, [
            'payment_status' => PaymentStatus::BelumDibayar->value,
            'laundry_status' => LaundryStatus::SiapDiambil->value,
        ]);

        $this->assertEquals(PaymentStatus::BelumDibayar, $orderUnpaid->payment_status);
        $this->assertFalse($orderUnpaid->is_paid);

        // Serah terima pakaian ke pelanggan (Selesai) tetap diizinkan secara operasional
        $response = $this->actingAs($this->kasir)->patch(route('orders.update-status', $orderUnpaid), [
            'status' => LaundryStatus::Selesai->value,
            'note' => 'Pakaian diserahkan, kasir mencatat pengingat tagihan.',
        ]);

        $response->assertRedirect(route('orders.show', $orderUnpaid));
        $response->assertSessionHas('success');

        $orderUnpaid->refresh();
        $this->assertEquals(LaundryStatus::Selesai, $orderUnpaid->laundry_status);
        // Status pembayaran tidak berubah secara gaib
        $this->assertEquals(PaymentStatus::BelumDibayar, $orderUnpaid->payment_status);
    }

    /**
     * Uji 11: Catatan status operasional tersimpan di histori dan activity log.
     */
    public function test_order_status_history_chronology_and_operator_notes(): void
    {
        $order = $this->createTestOrder($this->serviceCuciSetrika);

        $customNote = 'Pakaian dimasukkan ke mesin cuci front-loading #02 dengan detergen khusus.';

        $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::MenungguProses->value,
            'note' => $customNote,
        ]);

        $latestHistory = $order->statusHistories()->latest('id')->first();
        $this->assertEquals(LaundryStatus::MenungguProses, $latestHistory->to_status);
        $this->assertEquals(LaundryStatus::Diterima, $latestHistory->from_status);
        $this->assertEquals($this->kasir->id, $latestHistory->changed_by);
        $this->assertEquals($customNote, $latestHistory->note);
    }

    /**
     * Uji 12: Halaman detail pesanan merender panel status dan tombol transisi rekomendasi.
     */
    public function test_order_detail_view_renders_status_control_panel_and_stepper(): void
    {
        $order = $this->createTestOrder($this->serviceCuciSetrika);

        $response = $this->actingAs($this->kasir)->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertSee('Alur Status Pengerjaan Cucian');
        $response->assertSee('Lanjut ke: Menunggu Proses');
        $response->assertSee('Batalkan');
        $response->assertSee('Saat Ini');
    }
}

