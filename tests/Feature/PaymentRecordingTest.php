<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Enums\LaundryStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian Komprehensif Task 8.1 — Pencatatan Pembayaran Manual
 */
class PaymentRecordingTest extends TestCase
{
    use RefreshDatabase;

    protected User $kasir;
    protected User $admin;
    protected User $inactiveKasir;
    protected Customer $customer;
    protected Service $service;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kasir = User::factory()->create([
            'name' => 'Siti Kasir',
            'username' => 'kasir_siti',
            'role' => UserRole::Kasir,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin Utama',
            'username' => 'admin_laundry',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->inactiveKasir = User::factory()->create([
            'name' => 'Kasir Nonaktif',
            'username' => 'kasir_nonaktif',
            'role' => UserRole::Kasir,
            'is_active' => false,
        ]);

        $this->customer = Customer::factory()->create([
            'name' => 'Pak Ahmad',
            'phone' => '081234567890',
        ]);

        $this->service = Service::factory()->create([
            'name' => 'Cuci Komplit Reguler',
            'price' => 10000,
        ]);

        // Order dengan total Rp 50.000 (5 kg x 10.000)
        $this->order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->kasir->id,
            'subtotal' => 50000,
            'discount_amount' => 0,
            'extra_charge' => 0,
            'total_amount' => 50000,
            'laundry_status' => LaundryStatus::Diterima,
            'payment_status' => PaymentStatus::BelumDibayar,
            'received_at' => Carbon::now()->subHours(2),
        ]);

        OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'service_id' => $this->service->id,
            'service_name_snapshot' => $this->service->name,
            'billing_unit_snapshot' => \App\Enums\BillingUnit::Kg,
            'unit_price' => 10000,
            'quantity' => 5,
            'subtotal' => 50000,
        ]);
    }

    public function test_kasir_can_record_full_payment_and_order_status_becomes_paid(): void
    {
        $response = $this->actingAs($this->kasir)->post(route('orders.payments.store', $this->order), [
            'amount' => 50000,
            'payment_method' => PaymentMethod::Tunai->value,
            'cash_tendered' => 50000,
            'confirm_received' => '1',
            'notes' => 'Pembayaran lunas via kasir',
        ]);

        $response->assertRedirect(route('orders.show', $this->order));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'order_id' => $this->order->id,
            'recorded_by' => $this->kasir->id,
            'amount' => '50000.00',
            'method' => PaymentMethod::Tunai->value,
            'cash_tendered' => '50000.00',
            'change_amount' => '0.00',
            'status' => PaymentRecordStatus::Valid->value,
        ]);

        $this->order->refresh();
        $this->assertEquals(PaymentStatus::Lunas, $this->order->payment_status);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->kasir->id,
            'action' => ActivityAction::PaymentRecorded->value,
        ]);
    }

    public function test_kasir_can_record_partial_payment_and_order_status_becomes_partial(): void
    {
        $response = $this->actingAs($this->kasir)->post(route('orders.payments.store', $this->order), [
            'amount' => 20000,
            'payment_method' => PaymentMethod::Tunai->value,
            'cash_tendered' => 20000,
            'confirm_received' => '1',
            'notes' => 'Uang muka DP Rp 20.000',
        ]);

        $response->assertRedirect(route('orders.show', $this->order));

        $this->assertDatabaseHas('payments', [
            'order_id' => $this->order->id,
            'amount' => '20000.00',
            'status' => PaymentRecordStatus::Valid->value,
        ]);

        $this->order->refresh();
        $this->assertEquals(PaymentStatus::DibayarSebagian, $this->order->payment_status);
    }

    public function test_multiple_partial_payments_accumulate_to_reach_paid_status(): void
    {
        // Pembayaran pertama: 20.000
        $this->actingAs($this->kasir)->post(route('orders.payments.store', $this->order), [
            'amount' => 20000,
            'payment_method' => PaymentMethod::Tunai->value,
            'cash_tendered' => 20000,
            'confirm_received' => '1',
        ]);

        $this->order->refresh();
        $this->assertEquals(PaymentStatus::DibayarSebagian, $this->order->payment_status);

        // Pembayaran kedua: 30.000 (pelunasan)
        $this->actingAs($this->kasir)->post(route('orders.payments.store', $this->order), [
            'amount' => 30000,
            'payment_method' => PaymentMethod::TransferBank->value,
            'reference' => 'TRF-BCA-98765',
            'confirm_received' => '1',
        ]);

        $this->order->refresh();
        $this->assertEquals(PaymentStatus::Lunas, $this->order->payment_status);
        $this->assertEquals(2, $this->order->validPayments()->count());
    }

    public function test_overpayment_is_rejected_by_server_validation(): void
    {
        // Coba bayar 55.000 padahal tagihan hanya 50.000
        $response = $this->actingAs($this->kasir)->post(route('orders.payments.store', $this->order), [
            'amount' => 55000,
            'payment_method' => PaymentMethod::Tunai->value,
            'cash_tendered' => 60000,
            'confirm_received' => '1',
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('payments', 0);

        $this->order->refresh();
        $this->assertEquals(PaymentStatus::BelumDibayar, $this->order->payment_status);
    }

    public function test_non_positive_or_zero_payment_amount_is_rejected(): void
    {
        $response = $this->actingAs($this->kasir)->post(route('orders.payments.store', $this->order), [
            'amount' => 0,
            'payment_method' => PaymentMethod::Tunai->value,
            'confirm_received' => '1',
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_amount_with_thousands_separator_is_normalized_and_saved(): void
    {
        $response = $this->actingAs($this->kasir)->post(route('orders.payments.store', $this->order), [
            'amount' => '50.000',
            'payment_method' => PaymentMethod::Tunai->value,
            'cash_tendered' => '50.000',
            'confirm_received' => '1',
        ]);

        $response->assertRedirect(route('orders.show', $this->order));

        $this->assertDatabaseHas('payments', [
            'order_id' => $this->order->id,
            'amount' => '50000.00',
        ]);
    }

    public function test_cash_payment_with_tendered_greater_than_amount_computes_change_amount(): void
    {
        $response = $this->actingAs($this->kasir)->post(route('orders.payments.store', $this->order), [
            'amount' => 35000,
            'payment_method' => PaymentMethod::Tunai->value,
            'cash_tendered' => 50000,
            'confirm_received' => '1',
        ]);

        $response->assertRedirect(route('orders.show', $this->order));

        $this->assertDatabaseHas('payments', [
            'order_id' => $this->order->id,
            'amount' => '35000.00',
            'cash_tendered' => '50000.00',
            'change_amount' => '15000.00',
        ]);
    }

    public function test_cash_payment_with_tendered_less_than_amount_is_rejected(): void
    {
        $response = $this->actingAs($this->kasir)->post(route('orders.payments.store', $this->order), [
            'amount' => 50000,
            'payment_method' => PaymentMethod::Tunai->value,
            'cash_tendered' => 40000,
            'confirm_received' => '1',
        ]);

        $response->assertSessionHasErrors('cash_tendered');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_bank_transfer_and_qris_manual_payment_saves_reference(): void
    {
        $response = $this->actingAs($this->kasir)->post(route('orders.payments.store', $this->order), [
            'amount' => 50000,
            'payment_method' => PaymentMethod::QrisManual->value,
            'reference' => 'QRIS-RRN-202610130001',
            'confirm_received' => '1',
        ]);

        $response->assertRedirect(route('orders.show', $this->order));

        $this->assertDatabaseHas('payments', [
            'order_id' => $this->order->id,
            'method' => PaymentMethod::QrisManual->value,
            'reference' => 'QRIS-RRN-202610130001',
            'cash_tendered' => null,
            'change_amount' => null,
        ]);
    }

    public function test_other_payment_method_requires_notes(): void
    {
        $response = $this->actingAs($this->kasir)->post(route('orders.payments.store', $this->order), [
            'amount' => 50000,
            'payment_method' => PaymentMethod::Lainnya->value,
            'confirm_received' => '1',
            'notes' => '',
        ]);

        $response->assertSessionHasErrors('notes');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_confirmation_checkbox_is_required_to_record_payment(): void
    {
        $response = $this->actingAs($this->kasir)->post(route('orders.payments.store', $this->order), [
            'amount' => 50000,
            'payment_method' => PaymentMethod::Tunai->value,
            'cash_tendered' => 50000,
            // confirm_received omitted
        ]);

        $response->assertSessionHasErrors('confirm_received');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_cashier_payment_uses_server_timestamp_and_cannot_backdate(): void
    {
        $customDate = Carbon::now()->subHour()->format('Y-m-d H:i:s');

        $response = $this->actingAs($this->kasir)->post(route('orders.payments.store', $this->order), [
            'amount' => 50000,
            'payment_method' => PaymentMethod::Tunai->value,
            'cash_tendered' => 50000,
            'payment_date' => $customDate,
            'confirm_received' => '1',
        ]);

        $response->assertSessionHasErrors('payment_date');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_admin_can_backdate_payment_within_valid_received_at_window_with_reason(): void
    {
        $customDate = Carbon::now()->subHour()->format('Y-m-d H:i:s');

        $response = $this->actingAs($this->admin)->post(route('orders.payments.store', $this->order), [
            'amount' => 50000,
            'payment_method' => PaymentMethod::TransferBank->value,
            'payment_date' => $customDate,
            'confirm_received' => '1',
            'notes' => 'Pencatatan susulan transfer bank tadi sore',
        ]);

        $response->assertRedirect(route('orders.show', $this->order));

        $this->assertDatabaseHas('payments', [
            'order_id' => $this->order->id,
            'recorded_by' => $this->admin->id,
            'amount' => '50000.00',
            'payment_date' => $customDate,
        ]);
    }

    public function test_admin_cannot_backdate_prior_to_order_received_at(): void
    {
        // received_at order adalah 2 jam lalu, admin coba backdate 3 jam lalu
        $tooEarly = Carbon::now()->subHours(3)->format('Y-m-d H:i:s');

        $response = $this->actingAs($this->admin)->post(route('orders.payments.store', $this->order), [
            'amount' => 50000,
            'payment_method' => PaymentMethod::Tunai->value,
            'cash_tendered' => 50000,
            'payment_date' => $tooEarly,
            'confirm_received' => '1',
            'notes' => 'Alasan backdate',
        ]);

        $response->assertSessionHasErrors('payment_date');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_on_cancelled_order_is_rejected(): void
    {
        $this->order->update([
            'laundry_status' => LaundryStatus::Dibatalkan,
        ]);

        $response = $this->actingAs($this->kasir)->post(route('orders.payments.store', $this->order), [
            'amount' => 50000,
            'payment_method' => PaymentMethod::Tunai->value,
            'cash_tendered' => 50000,
            'confirm_received' => '1',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_on_already_paid_order_is_rejected(): void
    {
        // Bayar lunas dulu
        Payment::factory()->create([
            'order_id' => $this->order->id,
            'amount' => 50000,
            'status' => PaymentRecordStatus::Valid,
        ]);
        $this->order->update(['payment_status' => PaymentStatus::Lunas]);

        // Coba bayar lagi
        $response = $this->actingAs($this->kasir)->post(route('orders.payments.store', $this->order), [
            'amount' => 10000,
            'payment_method' => PaymentMethod::Tunai->value,
            'cash_tendered' => 10000,
            'confirm_received' => '1',
        ]);

        $response->assertForbidden();
        $this->assertEquals(1, $this->order->validPayments()->count());
    }

    public function test_inactive_user_cannot_record_payment(): void
    {
        $response = $this->actingAs($this->inactiveKasir)->post(route('orders.payments.store', $this->order), [
            'amount' => 50000,
            'payment_method' => PaymentMethod::Tunai->value,
            'cash_tendered' => 50000,
            'confirm_received' => '1',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('identity');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_creating_order_with_initial_payment_records_payment_atomically(): void
    {
        $receivedAt = Carbon::now()->format('Y-m-d\TH:i');

        $response = $this->actingAs($this->kasir)->post(route('orders.store'), [
            'customer_id' => $this->customer->id,
            'received_at' => $receivedAt,
            'items' => [
                [
                    'service_id' => $this->service->id,
                    'quantity' => 3, // 3 x 10.000 = 30.000
                ],
            ],
            'discount_amount' => 0,
            'extra_charge' => 0,
            'initial_payment' => [
                'amount' => 30000,
                'method' => PaymentMethod::Tunai->value,
                'cash_tendered' => 50000,
                'confirm_received' => '1',
                'notes' => 'Lunas saat setor pakaian',
            ],
        ]);

        $newOrder = Order::latest('id')->first();
        $this->assertNotNull($newOrder);
        $this->assertEquals(PaymentStatus::Lunas, $newOrder->payment_status);
        $this->assertEquals('30000.00', $newOrder->total_amount);

        $this->assertDatabaseHas('payments', [
            'order_id' => $newOrder->id,
            'amount' => '30000.00',
            'cash_tendered' => '50000.00',
            'change_amount' => '20000.00',
            'status' => PaymentRecordStatus::Valid->value,
        ]);
    }

    public function test_creating_order_with_initial_payment_exceeding_total_is_rejected(): void
    {
        $receivedAt = Carbon::now()->format('Y-m-d\TH:i');

        $response = $this->actingAs($this->kasir)->post(route('orders.store'), [
            'customer_id' => $this->customer->id,
            'received_at' => $receivedAt,
            'items' => [
                [
                    'service_id' => $this->service->id,
                    'quantity' => 2, // 2 x 10.000 = 20.000
                ],
            ],
            'discount_amount' => 0,
            'extra_charge' => 0,
            'initial_payment' => [
                'amount' => 25000, // Melebihi 20.000
                'method' => PaymentMethod::Tunai->value,
                'confirm_received' => '1',
            ],
        ]);

        $response->assertSessionHasErrors('initial_payment.amount');
    }
}
