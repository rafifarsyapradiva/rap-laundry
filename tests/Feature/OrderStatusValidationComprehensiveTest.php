<?php

namespace Tests\Feature;

use App\Enums\BillingUnit;
use App\Enums\LaundryStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian Komprehensif Task 7.3 — Pengujian Status dan Kepatuhan Gate 7
 *
 * Menguji 5 Parameter Kritis:
 * 1. Uji transisi yang valid (linear, bypassing tahap tidak relevan, pembatalan, koreksi admin).
 * 2. Uji transisi yang tidak valid (mundur oleh kasir, loncat ke selesai, mutasi status terminal, string acak, pembatalan tanpa alasan).
 * 3. Uji status siap diambil tetapi belum lunas (DP Rp0 dan bayar sebagian).
 * 4. Uji status lunas tetapi belum diambil (lunas di awal, tidak selesai sebelum diserahkan fisik).
 * 5. Pastikan status cucian terpisah dari status pembayaran (Gate 7 Compliance).
 */
class OrderStatusValidationComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $kasir;
    protected User $admin;
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
            'username' => 'kasir_gate7',
            'role' => UserRole::Kasir,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Agus Admin',
            'username' => 'admin_gate7',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->customer = Customer::factory()->create([
            'name' => 'Siti Nurhaliza',
            'phone' => '081211223344',
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
     * Helper pembuatan pesanan pengujian.
     */
    protected function createOrder(Service $service, float $qty = 3.0, array $attributes = []): Order
    {
        $totalAmount = $service->price * $qty;

        $order = Order::create(array_merge([
            'order_number' => 'LD-' . now()->format('Ymd') . '-00' . rand(10, 99) . rand(1, 9),
            'customer_id' => $this->customer->id,
            'created_by' => $this->kasir->id,
            'received_at' => now(),
            'estimated_completion_at' => now()->addHours(48),
            'subtotal' => $totalAmount,
            'discount_amount' => 0,
            'extra_charge' => 0,
            'total_amount' => $totalAmount,
            'laundry_status' => LaundryStatus::Diterima->value,
            'payment_status' => PaymentStatus::BelumDibayar->value,
        ], $attributes));

        OrderItem::create([
            'order_id' => $order->id,
            'service_id' => $service->id,
            'service_name_snapshot' => $service->name,
            'billing_unit_snapshot' => $service->billing_unit->value,
            'quantity' => $qty,
            'unit_price' => $service->price,
            'subtotal' => $totalAmount,
        ]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => null,
            'to_status' => $order->laundry_status->value,
            'changed_by' => $this->kasir->id,
            'note' => 'Pesanan baru diterima.',
        ]);

        return $order->fresh(['items', 'statusHistories', 'payments']);
    }

    // =========================================================================
    // PARAMETER 1: UJI TRANSISI YANG VALID
    // =========================================================================

    /**
     * 1.1 Uji transisi maju linear lengkap: Diterima -> Menunggu -> Dicuci -> Dikeringkan -> Disetrika -> Siap Diambil -> Selesai.
     */
    public function test_valid_forward_transitions_matrix(): void
    {
        $order = $this->createOrder($this->serviceCuciSetrika);

        $validSequence = [
            LaundryStatus::MenungguProses,
            LaundryStatus::Dicuci,
            LaundryStatus::Dikeringkan,
            LaundryStatus::Disetrika,
            LaundryStatus::SiapDiambil,
            LaundryStatus::Selesai,
        ];

        foreach ($validSequence as $target) {
            $this->actingAs($this->kasir)
                ->patch(route('orders.update-status', $order), [
                    'status' => $target->value,
                ])
                ->assertRedirect(route('orders.show', $order))
                ->assertSessionHas('success');

            $order->refresh();
            $this->assertEquals($target, $order->laundry_status);
        }

        $this->assertNotNull($order->completed_at);
        $this->assertEquals(LaundryStatus::Selesai, $order->laundry_status);
    }

    /**
     * 1.2 Uji transisi adaptif yang valid (Cuci Kering melewati Disetrika, Setrika Saja melewati Dicuci/Dikeringkan).
     */
    public function test_valid_adaptive_transitions_for_non_ironing_and_iron_only_services(): void
    {
        // A. Cuci Kering: Dikeringkan langsung ke Siap Diambil
        $orderCuciKering = $this->createOrder($this->serviceCuciKering, 2.0, [
            'laundry_status' => LaundryStatus::Dikeringkan->value,
        ]);

        $this->actingAs($this->kasir)
            ->patch(route('orders.update-status', $orderCuciKering), [
                'status' => LaundryStatus::SiapDiambil->value,
            ])
            ->assertRedirect(route('orders.show', $orderCuciKering))
            ->assertSessionHas('success');

        $this->assertEquals(LaundryStatus::SiapDiambil, $orderCuciKering->fresh()->laundry_status);

        // B. Setrika Saja: Menunggu Proses langsung ke Disetrika
        $orderSetrikaSaja = $this->createOrder($this->serviceSetrikaSaja, 2.0, [
            'laundry_status' => LaundryStatus::MenungguProses->value,
        ]);

        $this->actingAs($this->kasir)
            ->patch(route('orders.update-status', $orderSetrikaSaja), [
                'status' => LaundryStatus::Disetrika->value,
            ])
            ->assertRedirect(route('orders.show', $orderSetrikaSaja))
            ->assertSessionHas('success');

        $this->assertEquals(LaundryStatus::Disetrika, $orderSetrikaSaja->fresh()->laundry_status);
    }

    /**
     * 1.3 Uji pembatalan sah dari seluruh status aktif (Diterima, Menunggu, Dicuci, Dikeringkan, Disetrika, Siap Diambil).
     */
    public function test_valid_cancellation_from_any_active_status(): void
    {
        $activeStatuses = [
            LaundryStatus::Diterima,
            LaundryStatus::MenungguProses,
            LaundryStatus::Dicuci,
            LaundryStatus::Dikeringkan,
            LaundryStatus::Disetrika,
            LaundryStatus::SiapDiambil,
        ];

        foreach ($activeStatuses as $status) {
            $order = $this->createOrder($this->serviceCuciSetrika, 1.0, [
                'laundry_status' => $status->value,
            ]);

            $reason = "Dibatalkan dari tahap {$status->label()} atas permintaan pelanggan.";

            $this->actingAs($this->kasir)
                ->patch(route('orders.update-status', $order), [
                    'status' => LaundryStatus::Dibatalkan->value,
                    'cancellation_reason' => $reason,
                ])
                ->assertRedirect(route('orders.show', $order))
                ->assertSessionHas('success');

            $order->refresh();
            $this->assertEquals(LaundryStatus::Dibatalkan, $order->laundry_status);
            $this->assertTrue($order->is_cancelled);
            $this->assertEquals($reason, $order->cancellation_reason);
        }
    }

    /**
     * 1.4 Uji koreksi status mundur yang sah oleh Administrator.
     */
    public function test_valid_admin_backward_correction_transitions(): void
    {
        $order = $this->createOrder($this->serviceCuciSetrika, 2.0, [
            'laundry_status' => LaundryStatus::SiapDiambil->value,
        ]);

        // Admin mengoreksi status mundur ke Disetrika
        $this->actingAs($this->admin)
            ->patch(route('orders.correct-status', $order), [
                'status' => LaundryStatus::Disetrika->value,
                'reason' => 'Pakaian kurang rapi, perlu disetrika ulang bagian saku.',
            ])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('success');

        $this->assertEquals(LaundryStatus::Disetrika, $order->fresh()->laundry_status);
    }

    // =========================================================================
    // PARAMETER 2: UJI TRANSISI YANG TIDAK VALID
    // =========================================================================

    /**
     * 2.1 Transisi mundur oleh Kasir ditolak di server.
     */
    public function test_invalid_backward_transitions_by_cashier_are_strictly_rejected(): void
    {
        $order = $this->createOrder($this->serviceCuciSetrika, 2.0, [
            'laundry_status' => LaundryStatus::Dikeringkan->value,
        ]);

        $this->actingAs($this->kasir)
            ->patch(route('orders.update-status', $order), [
                'status' => LaundryStatus::Dicuci->value,
            ])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHasErrors('status');

        $this->assertEquals(LaundryStatus::Dikeringkan, $order->fresh()->laundry_status);
    }

    /**
     * 2.2 Loncat langsung ke Selesai tanpa melewati Siap Diambil ditolak di server.
     */
    public function test_invalid_skipping_directly_to_selesai_without_siap_diambil(): void
    {
        $invalidSourceStatuses = [
            LaundryStatus::Diterima,
            LaundryStatus::MenungguProses,
            LaundryStatus::Dicuci,
            LaundryStatus::Dikeringkan,
            LaundryStatus::Disetrika,
        ];

        foreach ($invalidSourceStatuses as $source) {
            $order = $this->createOrder($this->serviceCuciSetrika, 1.0, [
                'laundry_status' => $source->value,
            ]);

            $this->actingAs($this->kasir)
                ->patch(route('orders.update-status', $order), [
                    'status' => LaundryStatus::Selesai->value,
                ])
                ->assertRedirect(route('orders.show', $order))
                ->assertSessionHasErrors('status');

            $this->assertEquals($source, $order->fresh()->laundry_status);
        }
    }

    /**
     * 2.3 Mutasi pada status terminal (Selesai dan Dibatalkan) ditolak.
     */
    public function test_invalid_mutation_on_terminal_statuses(): void
    {
        // Selesai
        $completedOrder = $this->createOrder($this->serviceCuciSetrika, 1.0, [
            'laundry_status' => LaundryStatus::Selesai->value,
            'completed_at' => now(),
        ]);

        $this->actingAs($this->kasir)
            ->patch(route('orders.update-status', $completedOrder), [
                'status' => LaundryStatus::Dicuci->value,
            ])
            ->assertStatus(403);

        $this->assertEquals(LaundryStatus::Selesai, $completedOrder->fresh()->laundry_status);

        // Dibatalkan
        $cancelledOrder = $this->createOrder($this->serviceCuciSetrika, 1.0, [
            'laundry_status' => LaundryStatus::Dibatalkan->value,
            'cancelled_at' => now(),
            'cancelled_by' => $this->kasir->id,
            'cancellation_reason' => 'Sudah batal.',
        ]);

        $this->actingAs($this->kasir)
            ->patch(route('orders.update-status', $cancelledOrder), [
                'status' => LaundryStatus::MenungguProses->value,
            ])
            ->assertStatus(403);

        $this->assertEquals(LaundryStatus::Dibatalkan, $cancelledOrder->fresh()->laundry_status);
    }

    /**
     * 2.4 Nilai string status yang tidak ada di enum ditolak oleh validasi server.
     */
    public function test_invalid_status_string_is_rejected_by_form_request(): void
    {
        $order = $this->createOrder($this->serviceCuciSetrika);

        $this->actingAs($this->kasir)
            ->patch(route('orders.update-status', $order), [
                'status' => 'status_tidak_dikenal',
            ])
            ->assertSessionHasErrors('status');

        $this->assertEquals(LaundryStatus::Diterima, $order->fresh()->laundry_status);
    }

    /**
     * 2.5 Pembatalan tanpa alasan ditolak oleh validasi form request.
     */
    public function test_invalid_cancellation_without_reason_is_rejected(): void
    {
        $order = $this->createOrder($this->serviceCuciSetrika);

        $this->actingAs($this->kasir)
            ->patch(route('orders.update-status', $order), [
                'status' => LaundryStatus::Dibatalkan->value,
                'cancellation_reason' => '',
            ])
            ->assertSessionHasErrors('cancellation_reason');

        $this->assertEquals(LaundryStatus::Diterima, $order->fresh()->laundry_status);
    }

    // =========================================================================
    // PARAMETER 3: UJI STATUS SIAP DIAMBIL TETAPI BELUM LUNAS
    // =========================================================================

    /**
     * 3.1 Pesanan dengan pembayaran Belum Dibayar (DP Rp0) dapat berprogres hingga Siap Diambil.
     */
    public function test_order_ready_for_pickup_with_unpaid_status_scenario(): void
    {
        $order = $this->createOrder($this->serviceCuciSetrika, 3.0, [
            'total_amount' => 24000,
            'payment_status' => PaymentStatus::BelumDibayar->value,
            'laundry_status' => LaundryStatus::Diterima->value,
        ]);

        $this->assertEquals(0.0, $order->total_paid);
        $this->assertEquals(24000.0, $order->remaining_balance);
        $this->assertEquals(PaymentStatus::BelumDibayar, $order->payment_status);

        // Majukan status fisik: Diterima -> Menunggu -> Dicuci -> Dikeringkan -> Disetrika -> Siap Diambil
        $steps = [
            LaundryStatus::MenungguProses,
            LaundryStatus::Dicuci,
            LaundryStatus::Dikeringkan,
            LaundryStatus::Disetrika,
            LaundryStatus::SiapDiambil,
        ];

        foreach ($steps as $st) {
            $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
                'status' => $st->value,
            ]);
        }

        $order->refresh();
        $this->assertEquals(LaundryStatus::SiapDiambil, $order->laundry_status);

        // Verifikasi kepatuhan: Tagihan tetap belum dibayar dan sisa tagihan tetap utuh
        $this->assertEquals(PaymentStatus::BelumDibayar, $order->payment_status);
        $this->assertEquals(24000.0, $order->remaining_balance);
        $this->assertFalse($order->is_paid);

        // Verifikasi pada tampilan Blade detail pesanan
        $response = $this->actingAs($this->kasir)->get(route('orders.show', $order));
        $response->assertOk();
        $response->assertSee('Siap Diambil');
        $response->assertSee('Belum Dibayar');
        $response->assertSee('Rp 24.000');
    }

    /**
     * 3.2 Pesanan dengan pembayaran Dibayar Sebagian (DP 50%) dapat berprogres hingga Siap Diambil.
     */
    public function test_order_ready_for_pickup_with_partial_payment_scenario(): void
    {
        $order = $this->createOrder($this->serviceCuciSetrika, 4.0, [
            'total_amount' => 32000,
            'payment_status' => PaymentStatus::DibayarSebagian->value,
            'laundry_status' => LaundryStatus::Dicuci->value,
        ]);

        // Catat pembayaran DP Rp 16.000
        Payment::create([
            'order_id' => $order->id,
            'recorded_by' => $this->kasir->id,
            'payment_date' => now(),
            'amount' => 16000,
            'method' => PaymentMethod::Tunai->value,
            'status' => PaymentRecordStatus::Valid->value,
            'reference' => 'DP-001',
        ]);

        $this->assertEquals(16000.0, $order->fresh()->total_paid);
        $this->assertEquals(16000.0, $order->fresh()->remaining_balance);

        // Majukan ke Siap Diambil
        $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::Dikeringkan->value,
        ]);
        $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::Disetrika->value,
        ]);
        $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::SiapDiambil->value,
        ]);

        $order->refresh();
        $this->assertEquals(LaundryStatus::SiapDiambil, $order->laundry_status);
        $this->assertEquals(PaymentStatus::DibayarSebagian, $order->payment_status);
        $this->assertEquals(16000.0, $order->remaining_balance);
        $this->assertFalse($order->is_paid);
    }

    // =========================================================================
    // PARAMETER 4: UJI STATUS LUNAS TETAPI BELUM DIAMBIL
    // =========================================================================

    /**
     * 4.1 Pesanan yang telah lunas di muka dapat berprogres normal tanpa prematur menjadi Selesai.
     */
    public function test_order_paid_in_advance_progresses_through_stages_without_premature_completion(): void
    {
        $order = $this->createOrder($this->serviceCuciSetrika, 3.0, [
            'total_amount' => 24000,
            'payment_status' => PaymentStatus::Lunas->value,
            'laundry_status' => LaundryStatus::Diterima->value,
        ]);

        // Catat pembayaran lunas Rp 24.000 di awal
        Payment::create([
            'order_id' => $order->id,
            'recorded_by' => $this->kasir->id,
            'payment_date' => now(),
            'amount' => 24000,
            'method' => PaymentMethod::Tunai->value,
            'status' => PaymentRecordStatus::Valid->value,
        ]);

        $this->assertTrue($order->fresh()->is_paid);
        $this->assertEquals(0.0, $order->fresh()->remaining_balance);

        // Majukan hingga Siap Diambil
        $steps = [
            LaundryStatus::MenungguProses,
            LaundryStatus::Dicuci,
            LaundryStatus::Dikeringkan,
            LaundryStatus::Disetrika,
            LaundryStatus::SiapDiambil,
        ];

        foreach ($steps as $st) {
            $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
                'status' => $st->value,
            ]);
            $order->refresh();
            // Verifikasi status pembayaran tetap Lunas di setiap tahap
            $this->assertEquals(PaymentStatus::Lunas, $order->payment_status);
        }

        // Cucian berstatus Siap Diambil dan Lunas
        $this->assertEquals(LaundryStatus::SiapDiambil, $order->laundry_status);
        $this->assertEquals(PaymentStatus::Lunas, $order->payment_status);
        $this->assertNull($order->completed_at); // Belum selesai serah terima!

        // Tampilan detail pesanan menampilkan Lunas dan Siap Diambil
        $response = $this->actingAs($this->kasir)->get(route('orders.show', $order));
        $response->assertOk();
        $response->assertSee('Siap Diambil');
        $response->assertSee('Lunas (Rp 0)');
    }

    // =========================================================================
    // PARAMETER 5: INDEPENDENSI STATUS CUCIAN VS STATUS PEMBAYARAN (GATE 7)
    // =========================================================================

    /**
     * 5.1 Mutasi status cucian berkali-kali tidak memutasi data tabel payments atau payment_status.
     */
    public function test_laundry_status_mutation_does_not_mutate_payment_records_or_status(): void
    {
        $order = $this->createOrder($this->serviceCuciKering, 5.0, [
            'total_amount' => 30000,
            'payment_status' => PaymentStatus::DibayarSebagian->value,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'recorded_by' => $this->kasir->id,
            'payment_date' => now()->subDay(),
            'amount' => 15000,
            'method' => PaymentMethod::TransferBank->value,
            'status' => PaymentRecordStatus::Valid->value,
            'reference' => 'TF-123456',
        ]);

        $initialPaymentsCount = Payment::where('order_id', $order->id)->count();

        // Mutasikan status cucian beberapa kali
        $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::MenungguProses->value,
        ]);
        $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::Dicuci->value,
        ]);
        $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::Dikeringkan->value,
        ]);
        $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::SiapDiambil->value,
        ]);

        // Verifikasi integritas finansial: Tidak ada pembayaran baru / perubahan pembayaran
        $this->assertEquals($initialPaymentsCount, Payment::where('order_id', $order->id)->count());
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'amount' => '15000.00',
            'status' => 'valid',
        ]);

        $order->refresh();
        $this->assertEquals(PaymentStatus::DibayarSebagian, $order->payment_status);
        $this->assertEquals(15000.0, $order->total_paid);
        $this->assertEquals(15000.0, $order->remaining_balance);
    }

    /**
     * 5.2 Query scope readyAndUnpaid() dan paidNotCompleted() menyaring data secara ortogonal.
     */
    public function test_scope_ready_and_unpaid_and_scope_paid_not_completed(): void
    {
        // Order 1: Siap diambil + Belum lunas
        $order1 = $this->createOrder($this->serviceCuciSetrika, 1.0, [
            'laundry_status' => LaundryStatus::SiapDiambil->value,
            'payment_status' => PaymentStatus::BelumDibayar->value,
        ]);

        // Order 2: Siap diambil + Lunas
        $order2 = $this->createOrder($this->serviceCuciSetrika, 1.0, [
            'laundry_status' => LaundryStatus::SiapDiambil->value,
            'payment_status' => PaymentStatus::Lunas->value,
        ]);

        // Order 3: Dicuci + Lunas
        $order3 = $this->createOrder($this->serviceCuciSetrika, 1.0, [
            'laundry_status' => LaundryStatus::Dicuci->value,
            'payment_status' => PaymentStatus::Lunas->value,
        ]);

        // Order 4: Selesai + Lunas
        $order4 = $this->createOrder($this->serviceCuciSetrika, 1.0, [
            'laundry_status' => LaundryStatus::Selesai->value,
            'payment_status' => PaymentStatus::Lunas->value,
            'completed_at' => now(),
        ]);

        // Order 5: Dibatalkan + Lunas
        $order5 = $this->createOrder($this->serviceCuciSetrika, 1.0, [
            'laundry_status' => LaundryStatus::Dibatalkan->value,
            'payment_status' => PaymentStatus::Lunas->value,
            'cancelled_at' => now(),
        ]);

        // Verifikasi scopeReadyAndUnpaid: Hanya order1
        $readyAndUnpaidIds = Order::readyAndUnpaid()->pluck('id')->toArray();
        $this->assertContains($order1->id, $readyAndUnpaidIds);
        $this->assertNotContains($order2->id, $readyAndUnpaidIds);
        $this->assertNotContains($order3->id, $readyAndUnpaidIds);

        // Verifikasi scopePaidNotCompleted: order2 dan order3 (karena lunas dan belum selesai/batal)
        $paidNotCompletedIds = Order::paidNotCompleted()->pluck('id')->toArray();
        $this->assertContains($order2->id, $paidNotCompletedIds);
        $this->assertContains($order3->id, $paidNotCompletedIds);
        $this->assertNotContains($order1->id, $paidNotCompletedIds);
        $this->assertNotContains($order4->id, $paidNotCompletedIds);
        $this->assertNotContains($order5->id, $paidNotCompletedIds);
    }
}
