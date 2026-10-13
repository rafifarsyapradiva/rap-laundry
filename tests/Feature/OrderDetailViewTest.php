<?php

namespace Tests\Feature;

use App\Enums\BillingUnit;
use App\Enums\LaundryStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDetailViewTest extends TestCase
{
    use RefreshDatabase;

    protected User $kasir;
    protected User $admin;
    protected Customer $customer;
    protected Service $service;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kasir = User::factory()->create([
            'name' => 'Siti Kasir',
            'role' => UserRole::Kasir,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Pak Bos Admin',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->customer = Customer::factory()->create([
            'name' => 'Ahmad Dahlan',
            'customer_code' => 'CUST-0099',
            'phone' => '081234567899',
            'address' => 'Jl. Pahlawan No. 123',
            'is_active' => true,
        ]);

        $this->service = Service::factory()->create([
            'name' => 'Cuci Kering Standar',
            'billing_unit' => BillingUnit::Kg,
            'price' => '10000.00',
            'is_active' => true,
        ]);

        $this->order = Order::factory()->create([
            'order_number' => 'LD-20261013-0099',
            'customer_id' => $this->customer->id,
            'created_by' => $this->kasir->id,
            'received_at' => now(),
            'estimated_completion_at' => now()->addDays(2),
            'subtotal' => '50000.00',
            'discount_amount' => '5000.00',
            'extra_charge' => '2000.00',
            'total_amount' => '47000.00',
            'notes' => 'Tolong pisahkan pakaian putih',
            'laundry_status' => LaundryStatus::Diterima,
            'payment_status' => PaymentStatus::BelumDibayar,
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'service_id' => $this->service->id,
            'service_name_snapshot' => 'Cuci Kering Standar',
            'billing_unit_snapshot' => BillingUnit::Kg,
            'quantity' => '5.00',
            'unit_price' => '10000.00',
            'subtotal' => '50000.00',
            'notes' => 'Wangi lavender',
        ]);

        $this->order->statusHistories()->create([
            'from_status' => null,
            'to_status' => LaundryStatus::Diterima,
            'changed_by' => $this->kasir->id,
            'note' => 'Pesanan baru diterima kasir',
        ]);
    }

    /**
     * Tamu yang belum login dialihkan ke form login.
     */
    public function test_guest_cannot_view_order_detail(): void
    {
        $response = $this->get(route('orders.show', $this->order));
        $response->assertRedirect(route('login'));
    }

    /**
     * Kasir dan Admin aktif dapat mengakses halaman detail transaksi pesanan.
     */
    public function test_cashier_and_admin_can_view_order_detail(): void
    {
        $responseKasir = $this->actingAs($this->kasir)->get(route('orders.show', $this->order));
        $responseKasir->assertOk();
        $responseKasir->assertSee($this->order->order_number);

        $responseAdmin = $this->actingAs($this->admin)->get(route('orders.show', $this->order));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee($this->order->order_number);
    }

    /**
     * Halaman detail menampilkan profil lengkap pelanggan dan tautan ke halaman pelanggan.
     */
    public function test_order_detail_displays_customer_profile_and_link(): void
    {
        $response = $this->actingAs($this->kasir)->get(route('orders.show', $this->order));
        $response->assertOk();

        $response->assertSee('Ahmad Dahlan');
        $response->assertSee('CUST-0099');
        $response->assertSee('0812-3456-7899');
        $response->assertSee('Jl. Pahlawan No. 123');
        $response->assertSee(route('customers.show', $this->customer->id));
    }

    /**
     * Halaman detail menampilkan rincian item snapshot layanan.
     */
    public function test_order_detail_displays_snapshot_service_items_and_subtotals(): void
    {
        $response = $this->actingAs($this->kasir)->get(route('orders.show', $this->order));
        $response->assertOk();

        $response->assertSee('Cuci Kering Standar');
        $response->assertSee('5 kg');
        $response->assertSee('Rp 10.000');
        $response->assertSee('Rp 50.000');
        $response->assertSee('Wangi lavender');
        $response->assertSee('Tolong pisahkan pakaian putih');
    }

    /**
     * Halaman detail menampilkan kalkulasi finansial lengkap: subtotal, diskon, biaya tambahan, dan sisa tagihan.
     */
    public function test_order_detail_displays_financial_summary_total_and_balance(): void
    {
        $response = $this->actingAs($this->kasir)->get(route('orders.show', $this->order));
        $response->assertOk();

        $response->assertSee('Rp 50.000'); // Subtotal
        $response->assertSee('Rp 5.000');  // Diskon
        $response->assertSee('Rp 2.000');  // Extra charge
        $response->assertSee('Rp 47.000'); // Grand total
        $response->assertSee('Rp 47.000'); // Sisa tagihan (belum ada pembayaran)

        // Catat pembayaran sebesar Rp20.000
        Payment::create([
            'order_id' => $this->order->id,
            'recorded_by' => $this->kasir->id,
            'payment_date' => now(),
            'amount' => '20000.00',
            'method' => PaymentMethod::Tunai,
            'status' => PaymentRecordStatus::Valid,
        ]);

        $this->order->update(['payment_status' => PaymentStatus::DibayarSebagian]);

        $responseAfterPay = $this->actingAs($this->kasir)->get(route('orders.show', $this->order));
        $responseAfterPay->assertSee('Rp 20.000'); // Total dibayar
        $responseAfterPay->assertSee('Rp 27.000'); // Sisa tagihan
    }

    /**
     * Halaman detail menampilkan petugas penerima pesanan dan estimasi waktu selesai.
     */
    public function test_order_detail_displays_receiving_operator_and_timestamps(): void
    {
        $response = $this->actingAs($this->kasir)->get(route('orders.show', $this->order));
        $response->assertOk();

        $response->assertSee('Siti Kasir');
        $response->assertSee('Kasir');
        $response->assertSee($this->order->received_at->format('d-m-Y H:i'));
        $response->assertSee($this->order->estimated_completion_at->format('d-m-Y H:i'));
    }

    /**
     * Halaman detail menampilkan timeline riwayat status cucian secara kronologis.
     */
    public function test_order_detail_displays_status_history_timeline(): void
    {
        $response = $this->actingAs($this->kasir)->get(route('orders.show', $this->order));
        $response->assertOk();

        $response->assertSee('Riwayat Alur Status');
        $response->assertSee('Pesanan baru diterima kasir');
        $response->assertSee('Siti Kasir');
    }

    /**
     * Jika pesanan dibatalkan, detail pesanan menampilkan banner pembatalan, alasan, dan petugas pembatal.
     */
    public function test_order_detail_displays_cancelled_info_if_order_is_cancelled(): void
    {
        $this->order->update([
            'laundry_status' => LaundryStatus::Dibatalkan,
            'cancelled_at' => now(),
            'cancelled_by' => $this->admin->id,
            'cancellation_reason' => 'Pelanggan membatalkan karena salah memilih layanan',
        ]);

        $response = $this->actingAs($this->kasir)->get(route('orders.show', $this->order));
        $response->assertOk();

        $response->assertSee('Pesanan Ini Telah Dibatalkan');
        $response->assertSee('Pak Bos Admin');
        $response->assertSee('Pelanggan membatalkan karena salah memilih layanan');
    }

    /**
     * Pasca penyimpanan pesanan dari form, alur langsung diarahkan ke halaman detail pesanan (orders.show).
     */
    public function test_order_creation_redirects_directly_to_order_detail(): void
    {
        BusinessSetting::set(BusinessSetting::KEY_ORDER_NUMBER_PREFIX, 'LD');

        $payload = [
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'service_id' => $this->service->id,
                    'quantity' => 2,
                ],
            ],
            'discount_amount' => 0,
            'extra_charge' => 0,
        ];

        $response = $this->actingAs($this->kasir)->post(route('orders.store'), $payload);

        $newOrder = Order::latest('id')->first();
        $this->assertNotNull($newOrder);

        $response->assertRedirect(route('orders.show', $newOrder));
    }
}
