<?php

namespace Tests\Feature;

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
 * Pengujian Komprehensif Task 7.2 — Riwayat Status dan Koreksi Berbasis Role
 *
 * Memverifikasi:
 * 1. Perekaman status awal dari null ke diterima.
 * 2. Perekaman status sebelumnya, status baru, petugas, waktu, dan catatan opsional.
 * 3. Wewenang Admin melakukan koreksi status mundur dengan alasan valid.
 * 4. Koreksi status mundur dari Selesai mengosongkan kembali timestamp completed_at.
 * 5. Larangan bagi Kasir untuk melakukan koreksi status (403 Forbidden).
 * 6. Penolakan koreksi status tanpa alasan atau alasan terlalu pendek (< 5 karakter).
 * 7. Larangan koreksi status pada pesanan yang sudah dibatalkan.
 * 8. Pembatalan pesanan tidak menghapus jejak order, detail item, maupun riwayat status terdahulu.
 * 9. Tampilan detail pesanan merender timeline kronologis dan tombol koreksi khusus Admin.
 */
class OrderStatusHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $kasir;
    protected User $admin;
    protected Customer $customer;
    protected Service $service;
    protected OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kasir = User::factory()->create([
            'name' => 'Budi Kasir',
            'username' => 'kasir_riwayat',
            'role' => UserRole::Kasir,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Agus Pemilik',
            'username' => 'admin_riwayat',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->customer = Customer::factory()->create([
            'name' => 'Rina Wijaya',
            'phone' => '081298765432',
            'is_active' => true,
        ]);

        $this->service = Service::factory()->create([
            'name' => 'Cuci Setrika',
            'billing_unit' => BillingUnit::Kg,
            'price' => 8000,
            'is_active' => true,
        ]);

        $this->orderService = app(OrderService::class);
    }

    /**
     * Helper pembuatan pesanan pengujian.
     */
    protected function createTestOrder(array $attributes = []): Order
    {
        $order = Order::create(array_merge([
            'order_number' => 'LD-' . now()->format('Ymd') . '-00' . rand(10, 99),
            'customer_id' => $this->customer->id,
            'created_by' => $this->kasir->id,
            'received_at' => now(),
            'estimated_completion_at' => now()->addHours(48),
            'subtotal' => 24000,
            'discount_amount' => 0,
            'extra_charge' => 0,
            'total_amount' => 24000,
            'laundry_status' => LaundryStatus::Diterima->value,
            'payment_status' => PaymentStatus::BelumDibayar->value,
        ], $attributes));

        OrderItem::create([
            'order_id' => $order->id,
            'service_id' => $this->service->id,
            'service_name_snapshot' => $this->service->name,
            'billing_unit_snapshot' => $this->service->billing_unit->value,
            'quantity' => 3.0,
            'unit_price' => $this->service->price,
            'subtotal' => 24000,
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
     * Uji 1: Pembuatan pesanan baru otomatis merekam riwayat status awal (from_status = null, to_status = diterima).
     */
    public function test_initial_order_creation_records_from_null_to_diterima_history(): void
    {
        $order = $this->orderService->createOrder([
            'customer_id' => $this->customer->id,
            'received_at' => now()->toDateTimeString(),
            'items' => [
                ['service_id' => $this->service->id, 'quantity' => 2.5],
            ],
        ], $this->kasir);

        $this->assertCount(1, $order->statusHistories);
        $initialHistory = $order->statusHistories->first();

        $this->assertNull($initialHistory->from_status);
        $this->assertEquals(LaundryStatus::Diterima, $initialHistory->to_status);
        $this->assertEquals($this->kasir->id, $initialHistory->changed_by);
        $this->assertTrue($initialHistory->is_initial);
        $this->assertFalse($initialHistory->is_correction);
        $this->assertStringContainsString('WIB', $initialHistory->formatted_created_at);
    }

    /**
     * Uji 2: Transisi status merekam status asal, status baru, petugas, waktu, dan catatan opsional.
     */
    public function test_status_transition_records_previous_and_new_status_with_operator_and_timestamp(): void
    {
        $order = $this->createTestOrder();

        $customNote = 'Pakaian dimasukkan ke tabung cuci mesin #01.';

        $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::MenungguProses->value,
            'note' => $customNote,
        ]);

        $order->refresh();
        $this->assertCount(2, $order->statusHistories);

        $latest = $order->latestStatusHistory;
        $this->assertEquals(LaundryStatus::Diterima, $latest->from_status);
        $this->assertEquals(LaundryStatus::MenungguProses, $latest->to_status);
        $this->assertEquals($this->kasir->id, $latest->changed_by);
        $this->assertEquals($customNote, $latest->note);
        $this->assertFalse($latest->is_initial);
        $this->assertFalse($latest->is_correction);
    }

    /**
     * Uji 3: Admin dapat melakukan koreksi status mundur dengan alasan valid.
     */
    public function test_admin_can_correct_status_backwards_with_valid_reason(): void
    {
        // Pesanan berada di Dikeringkan
        $order = $this->createTestOrder([
            'laundry_status' => LaundryStatus::Dikeringkan->value,
        ]);

        $correctionReason = 'Ditemukan noda membandel pada kerah kemeja, perlu dicuci ulang.';

        $response = $this->actingAs($this->admin)->patch(route('orders.correct-status', $order), [
            'status' => LaundryStatus::Dicuci->value,
            'reason' => $correctionReason,
        ]);

        $response->assertRedirect(route('orders.show', $order));
        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(LaundryStatus::Dicuci, $order->laundry_status);

        $latestHistory = $order->latestStatusHistory;
        $this->assertEquals(LaundryStatus::Dikeringkan, $latestHistory->from_status);
        $this->assertEquals(LaundryStatus::Dicuci, $latestHistory->to_status);
        $this->assertEquals($this->admin->id, $latestHistory->changed_by);
        $this->assertTrue($latestHistory->is_correction);
        $this->assertStringContainsString($correctionReason, $latestHistory->note);

        // Jejak audit activity log mencatat koreksi
        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $order->id,
            'user_id' => $this->admin->id,
        ]);
    }

    /**
     * Uji 4: Koreksi status mundur dari Selesai mengosongkan kembali timestamp completed_at.
     */
    public function test_admin_correcting_from_selesai_resets_completed_at_timestamp(): void
    {
        $completedTime = now()->subHours(2);
        $order = $this->createTestOrder([
            'laundry_status' => LaundryStatus::Selesai->value,
            'completed_at' => $completedTime,
        ]);

        $this->assertNotNull($order->completed_at);

        // Admin mengoreksi status kembali ke Siap Diambil
        $response = $this->actingAs($this->admin)->patch(route('orders.correct-status', $order), [
            'status' => LaundryStatus::SiapDiambil->value,
            'reason' => 'Kasir salah mengklik selesai, pakaian sebenarnya belum diserahkan ke pelanggan.',
        ]);

        $response->assertRedirect(route('orders.show', $order));
        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(LaundryStatus::SiapDiambil, $order->laundry_status);
        $this->assertNull($order->completed_at); // completed_at berhasil direset
    }

    /**
     * Uji 5: Kasir dilarang keras melakukan koreksi status (403 Forbidden).
     */
    public function test_cashier_is_forbidden_from_correcting_status(): void
    {
        $order = $this->createTestOrder([
            'laundry_status' => LaundryStatus::Dikeringkan->value,
        ]);

        $response = $this->actingAs($this->kasir)->patch(route('orders.correct-status', $order), [
            'status' => LaundryStatus::Dicuci->value,
            'reason' => 'Kasir mencoba memundurkan status.',
        ]);

        $response->assertStatus(403);

        $order->refresh();
        $this->assertEquals(LaundryStatus::Dikeringkan, $order->laundry_status);
    }

    /**
     * Uji 6: Koreksi status gagal jika alasan kosong atau terlalu pendek (< 5 karakter).
     */
    public function test_correction_fails_if_reason_is_empty_or_too_short(): void
    {
        $order = $this->createTestOrder([
            'laundry_status' => LaundryStatus::Dikeringkan->value,
        ]);

        // 1. Alasan kosong
        $responseEmpty = $this->actingAs($this->admin)->patch(route('orders.correct-status', $order), [
            'status' => LaundryStatus::Dicuci->value,
            'reason' => '',
        ]);
        $responseEmpty->assertSessionHasErrors('reason');

        // 2. Alasan kurang dari 5 karakter
        $responseShort = $this->actingAs($this->admin)->patch(route('orders.correct-status', $order), [
            'status' => LaundryStatus::Dicuci->value,
            'reason' => 'test',
        ]);
        $responseShort->assertSessionHasErrors('reason');

        $order->refresh();
        $this->assertEquals(LaundryStatus::Dikeringkan, $order->laundry_status);
    }

    /**
     * Uji 7: Pesanan yang dibatalkan tidak boleh dikoreksi kembali.
     */
    public function test_cannot_correct_cancelled_order(): void
    {
        $order = $this->createTestOrder([
            'laundry_status' => LaundryStatus::Dibatalkan->value,
            'cancelled_at' => now(),
            'cancelled_by' => $this->admin->id,
            'cancellation_reason' => 'Dibatalkan oleh pelanggan.',
        ]);

        $response = $this->actingAs($this->admin)->patch(route('orders.correct-status', $order), [
            'status' => LaundryStatus::MenungguProses->value,
            'reason' => 'Mencoba menghidupkan pesanan batal.',
        ]);

        $response->assertStatus(403);

        $order->refresh();
        $this->assertEquals(LaundryStatus::Dibatalkan, $order->laundry_status);
    }

    /**
     * Uji 8: Pembatalan pesanan tidak menghapus transaksi, rincian item, maupun riwayat status terdahulu.
     */
    public function test_cancellation_does_not_delete_order_or_existing_history(): void
    {
        $order = $this->createTestOrder();

        // Majukan ke Menunggu Proses lalu ke Dicuci
        $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::MenungguProses->value,
            'note' => 'Masuk antrean.',
        ]);
        $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::Dicuci->value,
            'note' => 'Mulai dicuci.',
        ]);

        $order->refresh();
        $this->assertCount(3, $order->statusHistories);

        // Batalkan pesanan
        $cancellationReason = 'Pelanggan meminta dibatalkan karena terburu-buru.';
        $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::Dibatalkan->value,
            'cancellation_reason' => $cancellationReason,
        ]);

        $order->refresh();

        // 1. Order tetap ada di database
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'laundry_status' => 'dibatalkan']);
        $this->assertEquals($cancellationReason, $order->cancellation_reason);

        // 2. Seluruh baris item tetap utuh
        $this->assertCount(1, $order->items);
        $this->assertEquals(24000, (float) $order->total_amount);

        // 3. Seluruh riwayat terdahulu tetap ada, ditambah entri pembatalan (total 4)
        $this->assertCount(4, $order->statusHistories);
        $this->assertEquals(LaundryStatus::Diterima, $order->statusHistories[0]->to_status);
        $this->assertEquals(LaundryStatus::MenungguProses, $order->statusHistories[1]->to_status);
        $this->assertEquals(LaundryStatus::Dicuci, $order->statusHistories[2]->to_status);
        $this->assertEquals(LaundryStatus::Dibatalkan, $order->statusHistories[3]->to_status);
    }

    /**
     * Uji 9: Halaman detail pesanan menampilkan timeline kronologis, tombol koreksi khusus Admin, dan penanda koreksi.
     */
    public function test_order_detail_view_renders_enhanced_timeline_and_correction_badges(): void
    {
        $order = $this->createTestOrder([
            'laundry_status' => LaundryStatus::Dikeringkan->value,
        ]);

        // Lakukan koreksi oleh Admin
        $this->actingAs($this->admin)->patch(route('orders.correct-status', $order), [
            'status' => LaundryStatus::Dicuci->value,
            'reason' => 'Koreksi operasional cuci ulang.',
        ]);

        // 1. Tampilan untuk Admin
        $adminView = $this->actingAs($this->admin)->get(route('orders.show', $order));
        $adminView->assertOk();
        $adminView->assertSee('Riwayat Alur Status');
        $adminView->assertSee('Koreksi Status (Admin)');
        $adminView->assertSee('Koreksi Admin');
        $adminView->assertSee('Koreksi operasional cuci ulang.');

        // 2. Tampilan untuk Kasir (tidak melihat tombol koreksi)
        $cashierView = $this->actingAs($this->kasir)->get(route('orders.show', $order));
        $cashierView->assertOk();
        $cashierView->assertSee('Riwayat Alur Status');
        $cashierView->assertDontSee('Koreksi Status (Admin)');
        // Namun kasir tetap melihat jejak kronologis dan badge koreksi yang dilakukan Admin
        $cashierView->assertSee('Koreksi Admin');
    }
}
