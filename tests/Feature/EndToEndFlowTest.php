<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Enums\BillingUnit;
use App\Enums\LaundryStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndToEndFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $kasir;
    protected Service $serviceKiloan;
    protected Service $serviceSatuan;

    protected function setUp(): void
    {
        parent::setUp();

        // Siapkan akun Admin dan Kasir
        $this->admin = User::factory()->create([
            'name' => 'Owner Laundry',
            'username' => 'owner',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->kasir = User::factory()->create([
            'name' => 'Kasir Pratama',
            'username' => 'kasir_pratama',
            'role' => UserRole::Kasir,
            'is_active' => true,
        ]);

        // Siapkan Master Layanan
        $this->serviceKiloan = Service::factory()->create([
            'name' => 'Cuci Kering Setrika',
            'billing_unit' => BillingUnit::Kg,
            'price' => 10000,
            'is_active' => true,
        ]);

        $this->serviceSatuan = Service::factory()->create([
            'name' => 'Cuci Jas / Blazer',
            'billing_unit' => BillingUnit::Item,
            'price' => 25000,
            'is_active' => true,
        ]);

        // Pengaturan Usaha Bawaan
        BusinessSetting::set(BusinessSetting::KEY_BUSINESS_NAME, 'RAP Laundry Premium');
        BusinessSetting::set(BusinessSetting::KEY_ORDER_NUMBER_PREFIX, 'LD');
        BusinessSetting::set(BusinessSetting::KEY_PRINT_FORMAT, 'thermal');
    }

    /**
     * Uji Alur Penuh End-to-End Siklus Transaksi Kasir:
     * Kasir Login -> Buat Pelanggan -> Buat Pesanan Multi-layanan ->
     * Verifikasi Total & Nomor -> Catat Pembayaran DP -> Ubah Status Bertahap ->
     * Cetak Nota -> Catat Pelunasan -> Selesai Diambil -> Verifikasi Laporan & Audit Log.
     */
    public function test_complete_cashier_end_to_end_operational_lifecycle(): void
    {
        // 1. Kasir Login
        $responseLogin = $this->post(route('login.store'), [
            'identity' => 'kasir_pratama',
            'password' => 'password123',
        ]);
        $responseLogin->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->kasir);

        // 2. Buat Pelanggan Baru
        $responseCustomer = $this->actingAs($this->kasir)->post(route('customers.store'), [
            'name' => 'Ibu Siti Aminah',
            'phone' => '081298765432',
            'address' => 'Jl. Kenanga No. 42, RT 03/05',
        ]);
        $responseCustomer->assertRedirect();
        $customer = Customer::where('phone', '081298765432')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Ibu Siti Aminah', $customer->name);

        // 3. Buat Pesanan Baru dengan beberapa layanan (Kiloan: 3.5 kg & Satuan: 1 item)
        // Subtotal: (3.5 x 10.000 = 35.000) + (1 x 25.000 = 25.000) = 60.000
        $now = Carbon::now();
        $responseOrder = $this->actingAs($this->kasir)->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'received_at' => $now->format('Y-m-d H:i:s'),
            'estimated_completion_at' => $now->copy()->addDays(2)->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'service_id' => $this->serviceKiloan->id,
                    'quantity' => 3.5,
                    'notes' => 'Pakaian sehari-hari, pewangi lavender',
                ],
                [
                    'service_id' => $this->serviceSatuan->id,
                    'quantity' => 1,
                    'notes' => 'Jas kerja warna hitam',
                ],
            ],
            'discount_amount' => 0,
            'extra_charge' => 0,
            'notes' => 'Cucian express pelanggan reguler',
        ]);

        $order = Order::where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($order);
        $responseOrder->assertRedirect(route('orders.show', $order));

        // 4. Verifikasi Total Perhitungan dan Format Nomor Transaksi Unik
        $this->assertEquals(60000.00, (float) $order->total_amount);
        $this->assertEquals(60000.00, (float) $order->remaining_balance);
        $this->assertEquals(PaymentStatus::BelumDibayar, $order->payment_status);
        $this->assertEquals(LaundryStatus::Diterima, $order->laundry_status);
        $this->assertMatchesRegularExpression('/^LD-\d{8}-\d{4}$/', $order->order_number);
        $this->assertCount(2, $order->items);

        // 5. Catat Pembayaran Sebagian (DP Rp30.000 secara Tunai)
        $responsePayment1 = $this->actingAs($this->kasir)->post(route('orders.payments.store', $order), [
            'amount' => 30000,
            'payment_method' => PaymentMethod::Tunai->value,
            'cash_tendered' => 50000,
            'confirm_received' => 1,
            'notes' => 'Uang muka (DP) diterima saat serah terima cucian',
        ]);
        $responsePayment1->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $this->assertEquals(PaymentStatus::DibayarSebagian, $order->payment_status);
        $this->assertEquals(30000.00, (float) $order->remaining_balance);
        $this->assertEquals(30000.00, (float) $order->total_paid);

        // 6. Transisi Status Pengerjaan Cucian secara Bertahap
        $statusFlow = [
            LaundryStatus::Dicuci,
            LaundryStatus::Dikeringkan,
            LaundryStatus::Disetrika,
            LaundryStatus::SiapDiambil,
        ];

        foreach ($statusFlow as $targetStatus) {
            $responseStatus = $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
                'status' => $targetStatus->value,
                'note' => "Pengerjaan status {$targetStatus->label()} oleh kasir.",
            ]);
            $responseStatus->assertRedirect(route('orders.show', $order));

            $order->refresh();
            $this->assertEquals($targetStatus, $order->laundry_status);
        }

        // 7. Akses Cetak Nota (Thermal dan A4)
        $responsePrintThermal = $this->actingAs($this->kasir)->get(route('orders.print', [$order, 'format' => 'thermal']));
        $responsePrintThermal->assertStatus(200);
        $responsePrintThermal->assertSee($order->order_number);
        $responsePrintThermal->assertSee('Ibu Siti Aminah');
        $responsePrintThermal->assertSee('Rp30.000'); // Sisa tagihan / DP

        $responsePrintA4 = $this->actingAs($this->kasir)->get(route('orders.print', [$order, 'format' => 'a4']));
        $responsePrintA4->assertStatus(200);
        $responsePrintA4->assertSee($order->order_number);

        // 8. Catat Pelunasan Sisa Tagihan (Rp30.000 via Transfer Bank)
        $responsePayment2 = $this->actingAs($this->kasir)->post(route('orders.payments.store', $order), [
            'amount' => 30000,
            'payment_method' => PaymentMethod::TransferBank->value,
            'reference' => 'TRF-BCA-987654',
            'confirm_received' => 1,
            'notes' => 'Pelunasan sisa tagihan via transfer',
        ]);
        $responsePayment2->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $this->assertEquals(PaymentStatus::Lunas, $order->payment_status);
        $this->assertEquals(0.00, (float) $order->remaining_balance);
        $this->assertEquals(60000.00, (float) $order->total_paid);

        // 9. Ubah Status Menjadi Selesai / Cucian Diambil Pelanggan
        $responseSelesai = $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::Selesai->value,
            'note' => 'Cucian diambil langsung oleh Ibu Siti Aminah',
        ]);
        $responseSelesai->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $this->assertEquals(LaundryStatus::Selesai, $order->laundry_status);
        $this->assertNotNull($order->completed_at);

        // 10. Verifikasi Laporan Keuangan dan Jejak Audit oleh Admin
        $responseReports = $this->actingAs($this->admin)->get(route('admin.reports.index'));
        $responseReports->assertStatus(200);
        $responseReports->assertSee('60.000'); // Total kas masuk dari 2 pembayaran

        $responseAudit = $this->actingAs($this->admin)->get(route('admin.audit-logs.index'));
        $responseAudit->assertStatus(200);
        $responseAudit->assertSee($order->order_number);
    }

    /**
     * Uji Skenario Pembatalan Pesanan Sesuai Aturan Integritas Keuangan:
     * - Pesanan dengan pembayaran aktif TIDAK BISA dibatalkan secara langsung.
     * - Pembayaran wajib di-void terlebih dahulu oleh Admin.
     * - Setelah pembayaran bersih, pesanan baru dapat dibatalkan dengan alasan sah.
     */
    public function test_order_cancellation_flow_with_financial_integrity_enforcement(): void
    {
        $customer = Customer::factory()->create();
        $now = Carbon::now();

        // 1. Buat Pesanan Baru
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $this->kasir->id,
            'total_amount' => 50000,
            'laundry_status' => LaundryStatus::Diterima,
            'payment_status' => PaymentStatus::DibayarSebagian,
        ]);

        // Ada pembayaran aktif Rp20.000
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'recorded_by' => $this->kasir->id,
            'amount' => 20000,
            'status' => \App\Enums\PaymentRecordStatus::Valid,
        ]);

        // 2. Coba batalkan pesanan saat masih ada pembayaran aktif -> Wajib ditolak
        $responseCancelFailed = $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::Dibatalkan->value,
            'cancellation_reason' => 'Pelanggan membatalkan pesanan secara sepihak',
        ]);
        $responseCancelFailed->assertSessionHasErrors();

        $order->refresh();
        $this->assertEquals(LaundryStatus::Diterima, $order->laundry_status);

        // 3. Admin melakukan void terhadap pembayaran aktif dengan alasan sah
        $responseVoid = $this->actingAs($this->admin)->patch(route('payments.void', $payment), [
            'reason' => 'Pengembalian uang muka karena pesanan laundry dibatalkan oleh pelanggan',
        ]);
        $responseVoid->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $this->assertEquals(PaymentStatus::BelumDibayar, $order->payment_status);

        // 4. Setelah pembayaran divoid, batalkan pesanan dengan alasan sah (>= 10 karakter)
        $responseCancelSuccess = $this->actingAs($this->kasir)->patch(route('orders.update-status', $order), [
            'status' => LaundryStatus::Dibatalkan->value,
            'cancellation_reason' => 'Pelanggan membatalkan cucian dan uang muka telah dikembalikan penuh',
        ]);
        $responseCancelSuccess->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $this->assertEquals(LaundryStatus::Dibatalkan, $order->laundry_status);
        $this->assertNotNull($order->cancelled_at);
        $this->assertEquals($this->kasir->id, $order->cancelled_by);

        // 5. Pastikan audit log mencatat pembatalan pesanan
        $this->assertDatabaseHas('activity_logs', [
            'action' => ActivityAction::OrderCancelled->value,
            'subject_id' => $order->id,
        ]);
    }
}
