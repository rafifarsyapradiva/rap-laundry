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
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian Kepatuhan Gate 8 — Pemisahan Nilai Transaksi & Rekonsiliasi Kas Berdasarkan Tanggal Bayar
 */
class CashReceiptByPaymentDateTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $kasir;
    protected Customer $customer;
    protected Service $service;
    protected PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Admin Keuangan',
            'username' => 'admin_finance',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->kasir = User::factory()->create([
            'name' => 'Kasir Loket',
            'username' => 'kasir_loket',
            'role' => UserRole::Kasir,
            'is_active' => true,
        ]);

        $this->customer = Customer::factory()->create([
            'name' => 'Pelanggan Setia',
            'phone' => '081122334455',
        ]);

        $this->service = Service::factory()->create([
            'name' => 'Cuci Kiloan Reguler',
            'price' => 10000,
        ]);

        $this->paymentService = app(PaymentService::class);
    }

    public function test_cash_receipt_is_calculated_by_payment_date_not_order_received_date(): void
    {
        // 1. Pesanan Bulan Lalu (September 2026), diterima tanggal 25 September 2026
        $orderBulanLalu = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->kasir->id,
            'received_at' => Carbon::parse('2026-09-25 10:00:00'),
            'subtotal' => 100000,
            'discount_amount' => 0,
            'extra_charge' => 0,
            'total_amount' => 100000,
            'laundry_status' => LaundryStatus::Diterima,
            'payment_status' => PaymentStatus::BelumDibayar,
        ]);

        OrderItem::factory()->create([
            'order_id' => $orderBulanLalu->id,
            'service_id' => $this->service->id,
            'service_name_snapshot' => $this->service->name,
            'billing_unit_snapshot' => BillingUnit::Kg,
            'unit_price' => 10000,
            'quantity' => 10,
            'subtotal' => 100000,
        ]);

        // Pembayaran untuk pesanan bulan lalu BARU DIBAYAR pada 5 Oktober 2026 (Bulan Ini)
        Payment::factory()->create([
            'order_id' => $orderBulanLalu->id,
            'recorded_by' => $this->kasir->id,
            'amount' => 100000,
            'method' => PaymentMethod::TransferBank,
            'status' => PaymentRecordStatus::Valid,
            'payment_date' => Carbon::parse('2026-10-05 14:00:00'),
        ]);

        // 2. Pesanan Bulan Ini (Oktober 2026), diterima tanggal 10 Oktober 2026
        $orderBulanIni = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->kasir->id,
            'received_at' => Carbon::parse('2026-10-10 09:00:00'),
            'subtotal' => 50000,
            'discount_amount' => 0,
            'extra_charge' => 0,
            'total_amount' => 50000,
            'laundry_status' => LaundryStatus::Diterima,
            'payment_status' => PaymentStatus::BelumDibayar,
        ]);

        OrderItem::factory()->create([
            'order_id' => $orderBulanIni->id,
            'service_id' => $this->service->id,
            'service_name_snapshot' => $this->service->name,
            'billing_unit_snapshot' => BillingUnit::Kg,
            'unit_price' => 10000,
            'quantity' => 5,
            'subtotal' => 50000,
        ]);

        // Pembayaran pesanan bulan ini dibayar pada 10 Oktober 2026
        Payment::factory()->create([
            'order_id' => $orderBulanIni->id,
            'recorded_by' => $this->kasir->id,
            'amount' => 50000,
            'method' => PaymentMethod::Tunai,
            'status' => PaymentRecordStatus::Valid,
            'payment_date' => Carbon::parse('2026-10-10 09:15:00'),
        ]);

        // Verifikasi Penerimaan Kas Bulan September 2026:
        // Meskipun Order dibuat di September, tidak ada uang masuk di September -> Kas Rp 0
        $kasSeptember = $this->paymentService->cashReceivedBetween(
            Carbon::parse('2026-09-01 00:00:00'),
            Carbon::parse('2026-09-30 23:59:59')
        );
        $this->assertTrue($kasSeptember->isEqualTo(0));

        // Verifikasi Penerimaan Kas Bulan Oktober 2026:
        // Pembayaran Order Bulan Lalu (100.000) + Pembayaran Order Bulan Ini (50.000) = 150.000
        $kasOktober = $this->paymentService->cashReceivedBetween(
            Carbon::parse('2026-10-01 00:00:00'),
            Carbon::parse('2026-10-31 23:59:59')
        );
        $this->assertTrue($kasOktober->isEqualTo(150000));
    }

    public function test_voided_payments_are_excluded_from_cash_receipt_calculations(): void
    {
        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->kasir->id,
            'received_at' => Carbon::parse('2026-10-01 08:00:00'),
            'subtotal' => 80000,
            'discount_amount' => 0,
            'extra_charge' => 0,
            'total_amount' => 80000,
        ]);

        // Pembayaran Valid: 50.000
        Payment::factory()->create([
            'order_id' => $order->id,
            'recorded_by' => $this->kasir->id,
            'amount' => 50000,
            'method' => PaymentMethod::Tunai,
            'status' => PaymentRecordStatus::Valid,
            'payment_date' => Carbon::parse('2026-10-02 10:00:00'),
        ]);

        // Pembayaran yang di-Void: 30.000
        Payment::factory()->create([
            'order_id' => $order->id,
            'recorded_by' => $this->kasir->id,
            'amount' => 30000,
            'method' => PaymentMethod::TransferBank,
            'status' => PaymentRecordStatus::Voided,
            'voided_by' => $this->admin->id,
            'void_reason' => 'Bukti mutasi fiktif / tidak ditemukan di rekening',
            'voided_at' => Carbon::parse('2026-10-02 12:00:00'),
            'payment_date' => Carbon::parse('2026-10-02 11:00:00'),
        ]);

        $kasOktober = $this->paymentService->cashReceivedBetween(
            Carbon::parse('2026-10-01 00:00:00'),
            Carbon::parse('2026-10-31 23:59:59')
        );

        // Hanya pembayaran valid (50.000) yang terhitung, voided (30.000) wajib diabaikan
        $this->assertTrue($kasOktober->isEqualTo(50000));
    }

    public function test_gate_8_clear_separation_of_transaction_value_cash_received_and_remaining_balance(): void
    {
        // Order Rp 100.000
        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->kasir->id,
            'received_at' => Carbon::now()->subDay(),
            'subtotal' => 100000,
            'discount_amount' => 0,
            'extra_charge' => 0,
            'total_amount' => 100000,
            'laundry_status' => LaundryStatus::Diterima,
            'payment_status' => PaymentStatus::DibayarSebagian,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'service_id' => $this->service->id,
            'service_name_snapshot' => $this->service->name,
            'billing_unit_snapshot' => BillingUnit::Kg,
            'unit_price' => 10000,
            'quantity' => 10,
            'subtotal' => 100000,
        ]);

        // Pembayaran DP Rp 40.000
        Payment::factory()->create([
            'order_id' => $order->id,
            'recorded_by' => $this->kasir->id,
            'amount' => 40000,
            'method' => PaymentMethod::Tunai,
            'cash_tendered' => 50000,
            'change_amount' => 10000,
            'status' => PaymentRecordStatus::Valid,
            'payment_date' => Carbon::now()->subHours(5),
        ]);

        // Verifikasi pada level Model & Service
        $this->assertEquals(100000.0, (float) $order->total_amount); // Nilai Transaksi
        $this->assertEquals(40000.0, (float) $order->total_paid);     // Uang Diterima
        $this->assertEquals(60000.0, (float) $order->remaining_balance); // Saldo Tagihan

        // Verifikasi render visual pada halaman Detail Pesanan (Gate 8 UI distinction)
        $response = $this->actingAs($this->kasir)->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertSee('Nilai Transaksi');
        $response->assertSee('100.000');
        $response->assertSee('Uang Diterima');
        $response->assertSee('40.000');
        $response->assertSee('Saldo Tagihan');
        $response->assertSee('60.000');
    }
}
