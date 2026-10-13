<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $kasir;
    protected Customer $customer;
    protected Order $order;
    protected Payment $validPayment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Owner Admin',
            'username' => 'admin_test',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->kasir = User::factory()->create([
            'name' => 'Kasir Dian',
            'username' => 'kasir_test',
            'role' => UserRole::Kasir,
            'is_active' => true,
        ]);

        $this->customer = Customer::factory()->create([
            'name' => 'Siti Aminah',
            'phone' => '081234567890',
        ]);

        $this->order = Order::factory()->create([
            'order_number' => 'LD-20261013-0001',
            'customer_id' => $this->customer->id,
            'created_by' => $this->kasir->id,
            'subtotal' => 50000,
            'total_amount' => 50000,
            'payment_status' => PaymentStatus::Lunas,
        ]);

        $this->validPayment = Payment::factory()->create([
            'order_id' => $this->order->id,
            'recorded_by' => $this->kasir->id,
            'amount' => 50000,
            'method' => PaymentMethod::Tunai,
            'cash_tendered' => 60000,
            'change_amount' => 10000,
            'status' => PaymentRecordStatus::Valid,
            'payment_date' => Carbon::now()->subMinutes(10),
            'reference' => 'NOTA-KASIR-01',
            'notes' => 'Pembayaran lunas di depan',
        ]);
    }

    public function test_guest_is_redirected_to_login_when_accessing_payments_index(): void
    {
        $response = $this->get(route('payments.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_both_admin_and_cashier_can_access_payments_index(): void
    {
        // Admin
        $responseAdmin = $this->actingAs($this->admin)->get(route('payments.index'));
        $responseAdmin->assertOk();
        $responseAdmin->assertViewIs('payments.index');
        $responseAdmin->assertSee('Riwayat Transaksi Pembayaran');
        $responseAdmin->assertSee('LD-20261013-0001');
        $responseAdmin->assertSee('Siti Aminah');
        $responseAdmin->assertSee('Rp50.000');

        // Kasir
        $responseKasir = $this->actingAs($this->kasir)->get(route('payments.index'));
        $responseKasir->assertOk();
        $responseKasir->assertViewIs('payments.index');
        $responseKasir->assertSee('Riwayat Transaksi Pembayaran');
        $responseKasir->assertSee('LD-20261013-0001');
    }

    public function test_payments_index_renders_financial_metrics_accurately(): void
    {
        // Tambahkan pembayaran non-tunai (transfer)
        Payment::factory()->create([
            'order_id' => $this->order->id,
            'recorded_by' => $this->admin->id,
            'amount' => 25000,
            'method' => PaymentMethod::TransferBank,
            'status' => PaymentRecordStatus::Valid,
            'payment_date' => Carbon::now(),
        ]);

        // Tambahkan pembayaran yang sudah di-void
        Payment::factory()->create([
            'order_id' => $this->order->id,
            'recorded_by' => $this->kasir->id,
            'amount' => 15000,
            'method' => PaymentMethod::Tunai,
            'status' => PaymentRecordStatus::Voided,
            'voided_by' => $this->admin->id,
            'voided_at' => Carbon::now(),
            'void_reason' => 'Kesalahan input nominal nota salah',
            'payment_date' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('payments.index'));

        $response->assertOk();
        // Total valid: 50.000 + 25.000 = 75.000
        $response->assertSee('Rp75.000');
        // Kas tunai valid: 50.000
        $response->assertSee('Rp50.000');
        // Non-tunai valid: 25.000
        $response->assertSee('Rp25.000');
        // Voided count: 1 Transaksi
        $response->assertSee('1 Transaksi');
    }

    public function test_filter_by_keyword_matches_order_number_and_customer_name(): void
    {
        $customerB = Customer::factory()->create(['name' => 'Budi Santoso']);
        $orderB = Order::factory()->create([
            'order_number' => 'LD-20261013-9999',
            'customer_id' => $customerB->id,
        ]);
        Payment::factory()->create([
            'order_id' => $orderB->id,
            'recorded_by' => $this->kasir->id,
            'amount' => 30000,
            'method' => PaymentMethod::QrisManual,
            'status' => PaymentRecordStatus::Valid,
        ]);

        // Cari Siti Aminah
        $responseA = $this->actingAs($this->admin)->get(route('payments.index', ['q' => 'Siti']));
        $responseA->assertOk();
        $responseA->assertSee('LD-20261013-0001');
        $responseA->assertDontSee('LD-20261013-9999');

        // Cari nomor nota B
        $responseB = $this->actingAs($this->admin)->get(route('payments.index', ['q' => '9999']));
        $responseB->assertOk();
        $responseB->assertSee('LD-20261013-9999');
        $responseB->assertDontSee('LD-20261013-0001');
    }

    public function test_filter_by_payment_method(): void
    {
        Payment::factory()->create([
            'order_id' => $this->order->id,
            'recorded_by' => $this->kasir->id,
            'amount' => 20000,
            'method' => PaymentMethod::QrisManual,
            'reference' => 'QRIS-UNIQUE-REF',
            'status' => PaymentRecordStatus::Valid,
        ]);

        $response = $this->actingAs($this->admin)->get(route('payments.index', ['method' => 'qris_manual']));
        $response->assertOk();
        $response->assertSee('QRIS-UNIQUE-REF');
        $response->assertDontSee('NOTA-KASIR-01');
    }

    public function test_filter_by_record_status_valid_and_voided(): void
    {
        Payment::factory()->create([
            'order_id' => $this->order->id,
            'recorded_by' => $this->kasir->id,
            'amount' => 12000,
            'status' => PaymentRecordStatus::Voided,
            'void_reason' => 'Salah ketik nilai uang',
            'reference' => 'VOIDED-REF-123',
        ]);

        // Filter hanya yang Voided
        $responseVoid = $this->actingAs($this->admin)->get(route('payments.index', ['status' => 'voided']));
        $responseVoid->assertOk();
        $responseVoid->assertSee('VOIDED-REF-123');
        $responseVoid->assertDontSee('NOTA-KASIR-01');

        // Filter hanya yang Valid
        $responseValid = $this->actingAs($this->admin)->get(route('payments.index', ['status' => 'valid']));
        $responseValid->assertOk();
        $responseValid->assertSee('NOTA-KASIR-01');
        $responseValid->assertDontSee('VOIDED-REF-123');
    }

    public function test_admin_can_void_payment_and_redirect_back_to_payments_index(): void
    {
        $response = $this->actingAs($this->admin)->patch(route('payments.void', $this->validPayment), [
            'reason' => 'Pembatalan transaksi via halaman riwayat pembayaran',
            'redirect_to' => 'payments',
        ]);

        $response->assertRedirect(route('payments.index'));
        $response->assertSessionHas('success');

        $this->validPayment->refresh();
        $this->assertEquals(PaymentRecordStatus::Voided, $this->validPayment->status);
    }

    public function test_cashier_cannot_void_payment(): void
    {
        $response = $this->actingAs($this->kasir)->patch(route('payments.void', $this->validPayment), [
            'reason' => 'Mencoba membatalkan tanpa izin admin',
        ]);

        $response->assertForbidden();
    }
}
