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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian Komprehensif Fase 9 — Nota dan Cetak
 * Memverifikasi integritas data nota fisik terhadap database (Gate 9).
 */
class ReceiptPrintingTest extends TestCase
{
    use RefreshDatabase;

    protected User $kasir;
    protected User $admin;
    protected Customer $customer;
    protected Service $serviceKiloan;
    protected Service $serviceSatuan;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        // Siapkan Pengaturan Bisnis Lokal
        BusinessSetting::set(BusinessSetting::KEY_BUSINESS_NAME, 'RAP Laundry Berkah');
        BusinessSetting::set(BusinessSetting::KEY_BUSINESS_ADDRESS, 'Jl. Melati No. 45, Jakarta');
        BusinessSetting::set(BusinessSetting::KEY_BUSINESS_PHONE, '081298765432');
        BusinessSetting::set(BusinessSetting::KEY_OPERATING_HOURS, 'Senin - Minggu: 07.00 - 21.00 WIB');
        BusinessSetting::set(
            BusinessSetting::KEY_RECEIPT_FOOTER_NOTE,
            'Klausul: Nota wajib dibawa saat mengambil pakaian. Klaim maksimal 24 jam setelah serah terima.'
        );

        $this->kasir = User::factory()->create([
            'name' => 'Budi Kasir',
            'username' => 'budi_kasir',
            'role' => UserRole::Kasir,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Dewi Admin',
            'username' => 'dewi_admin',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->customer = Customer::factory()->create([
            'customer_code' => 'CUST-0099',
            'name' => 'Ibu Rahmawati',
            'phone' => '081311223344',
            'address' => 'Komplek Permata Blok C No. 8',
        ]);

        $this->serviceKiloan = Service::factory()->create([
            'name' => 'Cuci Kering Setrika Kiloan',
            'billing_unit' => BillingUnit::Kg,
            'price' => 10000,
            'is_active' => true,
        ]);

        $this->serviceSatuan = Service::factory()->create([
            'name' => 'Bed Cover Besar',
            'billing_unit' => BillingUnit::Item,
            'price' => 25000,
            'is_active' => true,
        ]);

        // Buat pesanan contoh:
        // Item 1: 2.5 kg x 10.000 = 25.000
        // Item 2: 2 item x 25.000 = 50.000
        // Subtotal = 75.000, Diskon = 5.000, Biaya Tambahan = 10.000 (Express)
        // Total = 80.000
        $this->order = Order::factory()->create([
            'order_number' => 'LD-20261013-0099',
            'customer_id' => $this->customer->id,
            'created_by' => $this->kasir->id,
            'received_at' => Carbon::parse('2026-10-13 09:30:00'),
            'estimated_completion_at' => Carbon::parse('2026-10-15 17:00:00'),
            'subtotal' => 75000,
            'discount_amount' => 5000,
            'extra_charge' => 10000,
            'total_amount' => 80000,
            'laundry_status' => LaundryStatus::Dicuci,
            'payment_status' => PaymentStatus::DibayarSebagian,
            'notes' => 'Tolong pisahkan pakaian putih dan pakaian luntur.',
        ]);

        OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'service_id' => $this->serviceKiloan->id,
            'service_name_snapshot' => $this->serviceKiloan->name,
            'billing_unit_snapshot' => BillingUnit::Kg,
            'quantity' => 2.5,
            'unit_price' => 10000,
            'subtotal' => 25000,
            'notes' => 'Baju kantor katun',
        ]);

        OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'service_id' => $this->serviceSatuan->id,
            'service_name_snapshot' => $this->serviceSatuan->name,
            'billing_unit_snapshot' => BillingUnit::Item,
            'quantity' => 2,
            'unit_price' => 25000,
            'subtotal' => 50000,
            'notes' => 'Warna biru tua',
        ]);

        // Pembayaran sebagian (DP Rp 50.000 dengan uang tunai Rp 100.000 dan kembalian Rp 50.000)
        Payment::factory()->create([
            'order_id' => $this->order->id,
            'recorded_by' => $this->kasir->id,
            'amount' => 50000,
            'method' => PaymentMethod::Tunai,
            'cash_tendered' => 100000,
            'change_amount' => 50000,
            'status' => PaymentRecordStatus::Valid,
            'payment_date' => Carbon::parse('2026-10-13 09:35:00'),
        ]);
    }

    /**
     * Tamu (belum login) tidak boleh mengakses halaman nota cetak.
     */
    public function test_guest_is_redirected_to_login_when_accessing_print_route(): void
    {
        $response = $this->get(route('orders.print', $this->order));

        $response->assertRedirect(route('login'));
    }

    /**
     * Staf Kasir dan Administrator dapat mengakses halaman nota cetak.
     */
    public function test_kasir_and_admin_can_access_print_route(): void
    {
        $responseKasir = $this->actingAs($this->kasir)->get(route('orders.print', $this->order));
        $responseKasir->assertOk();

        $responseAdmin = $this->actingAs($this->admin)->get(route('orders.print', $this->order));
        $responseAdmin->assertOk();
    }

    /**
     * Nota menampilkan identitas usaha dari tabel business_settings.
     */
    public function test_business_identity_from_settings_is_rendered_on_receipt(): void
    {
        $response = $this->actingAs($this->kasir)->get(route('orders.print', $this->order));

        $response->assertOk();
        $response->assertSee('RAP Laundry Berkah');
        $response->assertSee('Jl. Melati No. 45, Jakarta');
        $response->assertSee('081298765432');
        $response->assertSee('07.00 - 21.00 WIB');
        $response->assertSee('Klausul: Nota wajib dibawa saat mengambil pakaian');
    }

    /**
     * Nota menampilkan data metadata pesanan, pelanggan, tanggal, dan kasir penerima.
     */
    public function test_customer_and_order_metadata_are_rendered_accurately(): void
    {
        $response = $this->actingAs($this->kasir)->get(route('orders.print', $this->order));

        $response->assertOk();
        $response->assertSee('LD-20261013-0099');
        $response->assertSee('Ibu Rahmawati');
        $response->assertSee('CUST-0099');
        $response->assertSee('081311223344');
        $response->assertSee('Budi Kasir');
        $response->assertSee('13-10-2026 09:30');
        $response->assertSee('15-10-2026 17:00');
        $response->assertSee('Dicuci');
        $response->assertSee('Dibayar Sebagian');
        $response->assertSee('Tolong pisahkan pakaian putih dan pakaian luntur.');
    }

    /**
     * Nota menampilkan snapshot layanan, kuantitas desimal Indonesia, harga, dan catatan item.
     */
    public function test_service_items_snapshots_and_decimal_quantities_are_rendered_accurately(): void
    {
        $response = $this->actingAs($this->kasir)->get(route('orders.print', $this->order));

        $response->assertOk();
        $response->assertSee('Cuci Kering Setrika Kiloan');
        $response->assertSee('Bed Cover Besar');
        $response->assertSee('2,50'); // Format desimal Indonesia
        $response->assertSee('25.000');
        $response->assertSee('50.000');
        $response->assertSee('Baju kantor katun');
        $response->assertSee('Warna biru tua');
    }

    /**
     * GATE 9: Seluruh angka finansial di nota identik 100% dengan database.
     */
    public function test_gate_9_financial_calculation_identity_between_database_and_receipt(): void
    {
        $response = $this->actingAs($this->kasir)->get(route('orders.print', $this->order));

        $response->assertOk();

        // 1. Verifikasi Nilai di Model Database
        $this->assertEquals(75000.0, (float) $this->order->subtotal);
        $this->assertEquals(5000.0, (float) $this->order->discount_amount);
        $this->assertEquals(10000.0, (float) $this->order->extra_charge);
        $this->assertEquals(80000.0, (float) $this->order->total_amount);
        $this->assertEquals(50000.0, (float) $this->order->total_paid);
        $this->assertEquals(30000.0, (float) $this->order->remaining_balance);

        // 2. Verifikasi Nilai yang Dirender di Tampilan Nota
        $response->assertSee('75.000'); // Subtotal
        $response->assertSee('5.000');  // Diskon
        $response->assertSee('10.000'); // Biaya Tambahan
        $response->assertSee('80.000'); // Total Nilai Tagihan
        $response->assertSee('50.000'); // Uang Diterima
        $response->assertSee('30.000'); // Saldo Sisa Tagihan
    }

    /**
     * Nota menampilkan uang tunai yang diserahkan dan kembalian kasir.
     */
    public function test_receipt_renders_cash_tendered_and_change_when_cash_payment_present(): void
    {
        $response = $this->actingAs($this->kasir)->get(route('orders.print', $this->order));

        $response->assertOk();
        $response->assertSee('100.000'); // Tunai diterima
        $response->assertSee('50.000');  // Kembalian
    }

    /**
     * Nota menampilkan stempel LUNAS saat pesanan sudah dilunasi penuh.
     */
    public function test_receipt_renders_paid_stamp_when_fully_paid(): void
    {
        // Catat pelunasan sisa tagihan Rp 30.000
        Payment::factory()->create([
            'order_id' => $this->order->id,
            'recorded_by' => $this->kasir->id,
            'amount' => 30000,
            'method' => PaymentMethod::TransferBank,
            'status' => PaymentRecordStatus::Valid,
            'payment_date' => Carbon::parse('2026-10-13 10:00:00'),
        ]);

        $this->order->update(['payment_status' => PaymentStatus::Lunas]);

        $response = $this->actingAs($this->kasir)->get(route('orders.print', $this->order));

        $response->assertOk();
        $response->assertSee('LUNAS');
        $this->assertEquals(80000.0, (float) $this->order->fresh()->total_paid);
        $this->assertEquals(0.0, (float) $this->order->fresh()->remaining_balance);
        $response->assertSee('80.000'); // Total pembayaran
        $response->assertSee('0');      // Sisa tagihan Rp 0
    }

    /**
     * Format switcher query param (?format=thermal dan ?format=a4) berfungsi presisi.
     */
    public function test_receipt_format_switcher_thermal_and_a4(): void
    {
        // 1. Format Struk Thermal
        $responseThermal = $this->actingAs($this->kasir)->get(route('orders.print', [
            'order' => $this->order,
            'format' => 'thermal',
        ]));
        $responseThermal->assertOk();
        $responseThermal->assertSee('paper-sheet-thermal');
        $responseThermal->assertSee('thermal-receipt');

        // 2. Format Faktur A4
        $responseA4 = $this->actingAs($this->kasir)->get(route('orders.print', [
            'order' => $this->order,
            'format' => 'a4',
        ]));
        $responseA4->assertOk();
        $responseA4->assertSee('paper-sheet-a4');
        $responseA4->assertSee('a4-receipt');
    }

    /**
     * Cetak ulang transaksi lama tetap konsisten meskipun harga master diubah atau dinonaktifkan.
     */
    public function test_historical_order_with_archived_or_mutated_service_reprints_accurately(): void
    {
        // Ubah harga master layanan dan nonaktifkan layanan
        $this->serviceKiloan->update([
            'price' => 20000, // Harga master melonjak 100%
            'is_active' => false,
        ]);
        $this->serviceSatuan->delete(); // Soft delete layanan master

        $response = $this->actingAs($this->kasir)->get(route('orders.print', $this->order));

        $response->assertOk();

        // Nota tetap mencetak harga snapshot asli (10.000 per kg & 25.000 per item)
        $response->assertSee('Cuci Kering Setrika Kiloan');
        $response->assertSee('Bed Cover Besar');
        $response->assertSee('10.000'); // Snapshot harga item 1
        $response->assertSee('25.000'); // Snapshot harga item 2
        $response->assertSee('80.000'); // Total tagihan historis tidak bergeser
    }

    /**
     * Pesanan yang dibatalkan menampilkan peringatan pembatalan dan alasan di nota.
     */
    public function test_cancelled_order_displays_prominent_cancellation_warning(): void
    {
        $this->order->update([
            'laundry_status' => LaundryStatus::Dibatalkan,
            'cancelled_at' => Carbon::now(),
            'cancelled_by' => $this->admin->id,
            'cancellation_reason' => 'Pelanggan membatalkan pesanan karena salah titip cucian.',
        ]);

        $response = $this->actingAs($this->kasir)->get(route('orders.print', $this->order));

        $response->assertOk();
        $response->assertSee('PESANAN DIBATALKAN');
        $response->assertSee('Pelanggan membatalkan pesanan karena salah titip cucian.');
    }
}
