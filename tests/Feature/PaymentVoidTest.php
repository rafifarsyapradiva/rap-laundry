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
use App\Services\OrderService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian Komprehensif Task 8.2 — Koreksi Pembayaran (Void) & Integritas Finansial
 */
class PaymentVoidTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $kasir;
    protected Customer $customer;
    protected Service $service;
    protected Order $order;
    protected Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Owner Admin',
            'username' => 'admin_owner',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->kasir = User::factory()->create([
            'name' => 'Kasir Dian',
            'username' => 'kasir_dian',
            'role' => UserRole::Kasir,
            'is_active' => true,
        ]);

        $this->customer = Customer::factory()->create([
            'name' => 'Bu Ratna',
            'phone' => '089876543210',
        ]);

        $this->service = Service::factory()->create([
            'name' => 'Cuci Kering Lipat',
            'price' => 8000,
        ]);

        // Order Rp 40.000 (5 kg x 8.000)
        $this->order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->kasir->id,
            'subtotal' => 40000,
            'discount_amount' => 0,
            'extra_charge' => 0,
            'total_amount' => 40000,
            'laundry_status' => LaundryStatus::Diterima,
            'payment_status' => PaymentStatus::Lunas,
            'received_at' => Carbon::now()->subHours(1),
        ]);

        OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'service_id' => $this->service->id,
            'service_name_snapshot' => $this->service->name,
            'billing_unit_snapshot' => \App\Enums\BillingUnit::Kg,
            'unit_price' => 8000,
            'quantity' => 5,
            'subtotal' => 40000,
        ]);

        $this->payment = Payment::factory()->create([
            'order_id' => $this->order->id,
            'recorded_by' => $this->kasir->id,
            'amount' => 40000,
            'method' => PaymentMethod::Tunai,
            'cash_tendered' => 50000,
            'change_amount' => 10000,
            'status' => PaymentRecordStatus::Valid,
            'payment_date' => Carbon::now()->subMinutes(30),
        ]);
    }

    public function test_admin_can_void_valid_payment_with_reason_at_least_10_characters(): void
    {
        $reason = 'Kesalahan input nominal oleh kasir, pembayaran diganti transfer';

        $response = $this->actingAs($this->admin)->patch(route('payments.void', $this->payment), [
            'reason' => $reason,
        ]);

        $response->assertRedirect(route('orders.show', $this->order));
        $response->assertSessionHas('success');

        $this->payment->refresh();
        $this->assertEquals(PaymentRecordStatus::Voided, $this->payment->status);
        $this->assertEquals($this->admin->id, $this->payment->voided_by);
        $this->assertEquals($reason, $this->payment->void_reason);
        $this->assertNotNull($this->payment->voided_at);

        // Jejak audit tercatat
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => ActivityAction::PaymentVoided->value,
            'subject_id' => $this->payment->id,
        ]);
    }

    public function test_voiding_only_payment_reverts_order_to_unpaid_status(): void
    {
        $this->actingAs($this->admin)->patch(route('payments.void', $this->payment), [
            'reason' => 'Pelanggan membatalkan transaksi tunai di kasir',
        ]);

        $this->order->refresh();
        $this->assertEquals(PaymentStatus::BelumDibayar, $this->order->payment_status);
        $this->assertEquals(0.0, $this->order->total_paid);
        $this->assertEquals(40000.0, $this->order->remaining_balance);
    }

    public function test_voiding_one_of_multiple_payments_reverts_order_from_paid_to_partial(): void
    {
        // Ubah skenario: order Rp 40.000 dengan 2 pembayaran (20.000 + 20.000)
        $this->payment->updateQuietly(['amount' => 20000]);

        $secondPayment = Payment::factory()->create([
            'order_id' => $this->order->id,
            'recorded_by' => $this->kasir->id,
            'amount' => 20000,
            'method' => PaymentMethod::TransferBank,
            'status' => PaymentRecordStatus::Valid,
            'payment_date' => Carbon::now()->subMinutes(15),
        ]);

        $this->order->refresh();
        $this->assertEquals(PaymentStatus::Lunas, $this->order->payment_status);
        $this->assertEquals(40000.0, $this->order->total_paid);

        // Admin void pembayaran kedua
        $this->actingAs($this->admin)->patch(route('payments.void', $secondPayment), [
            'reason' => 'Transfer bank ternyata gagal settling / ditolak bank',
        ]);

        $this->order->refresh();
        $this->assertEquals(PaymentStatus::DibayarSebagian, $this->order->payment_status);
        $this->assertEquals(20000.0, $this->order->total_paid);
        $this->assertEquals(20000.0, $this->order->remaining_balance);
    }

    public function test_non_admin_kasir_cannot_void_payment_and_gets_403(): void
    {
        $response = $this->actingAs($this->kasir)->patch(route('payments.void', $this->payment), [
            'reason' => 'Kasir ingin membatalkan pembayarannya sendiri',
        ]);

        $response->assertForbidden();

        $this->payment->refresh();
        $this->assertEquals(PaymentRecordStatus::Valid, $this->payment->status);
    }

    public function test_void_with_reason_less_than_10_characters_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)->patch(route('payments.void', $this->payment), [
            'reason' => 'Salah', // hanya 5 karakter
        ]);

        $response->assertSessionHasErrors('reason');

        $this->payment->refresh();
        $this->assertEquals(PaymentRecordStatus::Valid, $this->payment->status);
    }

    public function test_cannot_void_already_voided_payment(): void
    {
        // Void pertama kali
        $this->payment->updateQuietly([
            'status' => PaymentRecordStatus::Voided,
            'voided_by' => $this->admin->id,
            'void_reason' => 'Pembatalan awal yang sudah sah dilakukan',
            'voided_at' => now(),
        ]);

        // Coba void kedua kali
        $response = $this->actingAs($this->admin)->patch(route('payments.void', $this->payment), [
            'reason' => 'Mencoba void ulang pembayaran yang sudah batal',
        ]);

        $response->assertForbidden();
    }

    public function test_payment_is_immutable_against_silent_financial_edits(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("Atribut 'amount' pada transaksi pembayaran bersifat permanen");

        $this->payment->amount = 99999;
        $this->payment->save();
    }

    public function test_payment_method_is_immutable_against_silent_edits(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("Atribut 'method' pada transaksi pembayaran bersifat permanen");

        $this->payment->method = PaymentMethod::TransferBank;
        $this->payment->save();
    }

    public function test_payment_hard_delete_is_strictly_blocked(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Data pembayaran historis tidak boleh dihapus dari sistem');

        $this->payment->delete();
    }

    public function test_order_cannot_be_cancelled_if_valid_payments_exist(): void
    {
        $orderService = app(OrderService::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('tidak dapat dibatalkan karena masih memiliki pembayaran berstatus valid');

        $orderService->updateLaundryStatus(
            $this->order,
            LaundryStatus::Dibatalkan,
            $this->admin,
            null,
            'Batalkan pesanan ini sekarang'
        );
    }

    public function test_order_can_be_cancelled_after_all_payments_are_voided(): void
    {
        $orderService = app(OrderService::class);

        // Void semua pembayaran yang ada
        $this->actingAs($this->admin)->patch(route('payments.void', $this->payment), [
            'reason' => 'Pelanggan membatalkan pesanan sebelum diproses',
        ]);

        $this->order->refresh();
        $this->assertEquals(0, $this->order->validPayments()->count());

        // Sekarang pembatalan diizinkan
        $cancelledOrder = $orderService->updateLaundryStatus(
            $this->order,
            LaundryStatus::Dibatalkan,
            $this->admin,
            null,
            'Pesanan dibatalkan atas permintaan pelanggan'
        );

        $this->assertEquals(LaundryStatus::Dibatalkan, $cancelledOrder->laundry_status);
        $this->assertTrue($cancelledOrder->is_cancelled);
    }
}
