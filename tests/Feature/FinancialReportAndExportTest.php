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
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialReportAndExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $cashier;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create([
            'role' => UserRole::Kasir,
            'is_active' => true,
        ]);

        $this->customer = Customer::factory()->create();
    }

    public function test_guest_is_redirected_and_cashier_is_forbidden_from_reports_and_export(): void
    {
        // 1. Guest diarahkan ke login
        $this->get(route('admin.reports.index'))->assertRedirect(route('login'));
        $this->get(route('admin.reports.orders.export'))->assertRedirect(route('login'));
        $this->get(route('admin.reports.payments.export'))->assertRedirect(route('login'));

        // 2. Kasir ditolak 403 Forbidden
        $this->actingAs($this->cashier)
            ->get(route('admin.reports.index'))
            ->assertForbidden();

        $this->actingAs($this->cashier)
            ->get(route('admin.reports.orders.export'))
            ->assertForbidden();

        $this->actingAs($this->cashier)
            ->get(route('admin.reports.payments.export'))
            ->assertForbidden();
    }

    public function test_admin_can_access_reports_dashboard(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.index'));

        $response->assertOk()
            ->assertViewIs('reports.index')
            ->assertViewHasAll([
                'startDateStr',
                'endDateStr',
                'summary',
                'paymentMethods',
                'topServices',
                'laundryStatusCounts',
                'paymentStatusCounts',
            ]);
    }

    public function test_financial_summary_metrics_match_known_test_fixtures(): void
    {
        $targetDate = Carbon::parse('2026-10-15 10:00:00');

        // Order 1: Total 100.000, bayar valid 100.000 (Lunas)
        $order1 = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->cashier->id,
            'received_at' => $targetDate,
            'total_amount' => 100000,
            'payment_status' => PaymentStatus::Lunas,
            'laundry_status' => LaundryStatus::Selesai,
        ]);
        Payment::factory()->create([
            'order_id' => $order1->id,
            'recorded_by' => $this->cashier->id,
            'amount' => 100000,
            'method' => PaymentMethod::Tunai,
            'status' => PaymentRecordStatus::Valid,
            'payment_date' => $targetDate,
        ]);

        // Order 2: Total 80.000, bayar valid 30.000 (Sebagian) -> Sisa 50.000
        $order2 = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->cashier->id,
            'received_at' => $targetDate,
            'total_amount' => 80000,
            'payment_status' => PaymentStatus::DibayarSebagian,
            'laundry_status' => LaundryStatus::Dicuci,
        ]);
        Payment::factory()->create([
            'order_id' => $order2->id,
            'recorded_by' => $this->cashier->id,
            'amount' => 30000,
            'method' => PaymentMethod::TransferBank,
            'status' => PaymentRecordStatus::Valid,
            'payment_date' => $targetDate,
        ]);

        // Order 3: Total 40.000, Belum Bayar sama sekali -> Sisa 40.000
        Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->cashier->id,
            'received_at' => $targetDate,
            'total_amount' => 40000,
            'payment_status' => PaymentStatus::BelumDibayar,
            'laundry_status' => LaundryStatus::MenungguProses,
        ]);

        // Order 4: Total 50.000, Dibatalkan -> Tidak dihitung dalam omzet aktif
        Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->cashier->id,
            'received_at' => $targetDate,
            'total_amount' => 50000,
            'payment_status' => PaymentStatus::BelumDibayar,
            'laundry_status' => LaundryStatus::Dibatalkan,
        ]);

        $service = app(ReportService::class);
        $summary = $service->getSummaryMetrics($targetDate->copy()->startOfDay(), $targetDate->copy()->endOfDay());

        // Total 4 pesanan masuk (3 aktif, 1 batal)
        $this->assertSame(4, $summary['orders_count']);
        $this->assertSame(3, $summary['active_orders_count']);
        $this->assertSame(1, $summary['cancelled_orders_count']);

        // Nilai Transaksi / Omzet aktif: 100.000 + 80.000 + 40.000 = 220.000 (Order batal 50.000 tidak dihitung)
        $this->assertEquals(220000.0, $summary['gross_revenue']);

        // Kas Masuk riil: 100.000 (Tunai) + 30.000 (Transfer) = 130.000
        $this->assertEquals(130000.0, $summary['cash_inflow']);

        // Sisa Tagihan / Piutang: 50.000 (Order 2) + 40.000 (Order 3) = 90.000
        $this->assertEquals(90000.0, $summary['outstanding_balance']);
    }

    public function test_voided_payments_are_strictly_excluded_from_cash_inflow_report(): void
    {
        $targetDate = Carbon::parse('2026-10-15 11:00:00');

        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->cashier->id,
            'received_at' => $targetDate,
            'total_amount' => 200000,
        ]);

        // Pembayaran 1: Rp70.000 Valid
        Payment::factory()->create([
            'order_id' => $order->id,
            'recorded_by' => $this->cashier->id,
            'amount' => 70000,
            'status' => PaymentRecordStatus::Valid,
            'payment_date' => $targetDate,
        ]);

        // Pembayaran 2: Rp50.000 Di-void (Dibatalkan)
        Payment::factory()->create([
            'order_id' => $order->id,
            'recorded_by' => $this->cashier->id,
            'amount' => 50000,
            'status' => PaymentRecordStatus::Voided,
            'payment_date' => $targetDate,
        ]);

        $service = app(ReportService::class);
        $summary = $service->getSummaryMetrics($targetDate->copy()->startOfDay(), $targetDate->copy()->endOfDay());

        // Kas masuk hanya Rp70.000, Rp50.000 yang di-void TIDAK BOLEH dihitung!
        $this->assertEquals(70000.0, $summary['cash_inflow']);
    }

    public function test_payment_method_breakdown_aggregates_accurately(): void
    {
        $targetDate = Carbon::parse('2026-10-15 12:00:00');
        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->cashier->id,
            'received_at' => $targetDate,
            'total_amount' => 300000,
        ]);

        // 2x Tunai @ 25.000 = 50.000
        Payment::factory()->count(2)->create([
            'order_id' => $order->id,
            'recorded_by' => $this->cashier->id,
            'amount' => 25000,
            'method' => PaymentMethod::Tunai,
            'status' => PaymentRecordStatus::Valid,
            'payment_date' => $targetDate,
        ]);

        // 1x QRIS @ 75.000
        Payment::factory()->create([
            'order_id' => $order->id,
            'recorded_by' => $this->cashier->id,
            'amount' => 75000,
            'method' => PaymentMethod::QrisManual,
            'status' => PaymentRecordStatus::Valid,
            'payment_date' => $targetDate,
        ]);

        $service = app(ReportService::class);
        $breakdown = $service->getPaymentMethodBreakdown($targetDate->copy()->startOfDay(), $targetDate->copy()->endOfDay());

        $tunai = $breakdown->firstWhere('method_value', PaymentMethod::Tunai->value);
        $this->assertSame(2, $tunai['transaction_count']);
        $this->assertEquals(50000.0, $tunai['total_amount']);

        $qris = $breakdown->firstWhere('method_value', PaymentMethod::QrisManual->value);
        $this->assertSame(1, $qris['transaction_count']);
        $this->assertEquals(75000.0, $qris['total_amount']);
    }

    public function test_top_services_aggregates_frequency_quantity_and_subtotal(): void
    {
        $targetDate = Carbon::parse('2026-10-15 13:00:00');
        $serviceCuci = Service::factory()->create([
            'name' => 'Cuci Kering Reguler',
            'billing_unit' => BillingUnit::Kg,
            'price' => 7000,
        ]);
        $serviceBedcover = Service::factory()->create([
            'name' => 'Bedcover Besar',
            'billing_unit' => BillingUnit::Item,
            'price' => 30000,
        ]);

        // Order 1: 5 kg Cuci Kering (Subtotal 35.000)
        $order1 = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->cashier->id,
            'received_at' => $targetDate,
            'laundry_status' => LaundryStatus::Dicuci,
        ]);
        OrderItem::create([
            'order_id' => $order1->id,
            'service_id' => $serviceCuci->id,
            'service_name_snapshot' => 'Cuci Kering Reguler',
            'billing_unit_snapshot' => BillingUnit::Kg,
            'quantity' => 5.00,
            'unit_price' => 7000,
            'subtotal' => 35000,
        ]);

        // Order 2: 10 kg Cuci Kering (Subtotal 70.000) + 1 Item Bedcover (Subtotal 30.000)
        $order2 = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->cashier->id,
            'received_at' => $targetDate,
            'laundry_status' => LaundryStatus::MenungguProses,
        ]);
        OrderItem::create([
            'order_id' => $order2->id,
            'service_id' => $serviceCuci->id,
            'service_name_snapshot' => 'Cuci Kering Reguler',
            'billing_unit_snapshot' => BillingUnit::Kg,
            'quantity' => 10.00,
            'unit_price' => 7000,
            'subtotal' => 70000,
        ]);
        OrderItem::create([
            'order_id' => $order2->id,
            'service_id' => $serviceBedcover->id,
            'service_name_snapshot' => 'Bedcover Besar',
            'billing_unit_snapshot' => BillingUnit::Item,
            'quantity' => 1.00,
            'unit_price' => 30000,
            'subtotal' => 30000,
        ]);

        $service = app(ReportService::class);
        $topServices = $service->getTopServices($targetDate->copy()->startOfDay(), $targetDate->copy()->endOfDay());

        // Cuci Kering Reguler teratas: Total 15 kg, 2 pesanan, total subtotal 105.000
        $top = $topServices->firstWhere('service_name', 'Cuci Kering Reguler');
        $this->assertNotNull($top);
        $this->assertSame(2, $top['order_frequency']);
        $this->assertEquals(15.0, $top['total_quantity']);
        $this->assertEquals(105000.0, $top['total_subtotal']);
    }

    public function test_csv_export_orders_streams_valid_content_with_bom_and_sanitization(): void
    {
        $targetDate = Carbon::parse('2026-10-15 14:00:00');
        $order = Order::factory()->create([
            'order_number' => '=1+1Malicious', // Simulasi string berpotensi CSV Injection
            'customer_id' => $this->customer->id,
            'created_by' => $this->cashier->id,
            'received_at' => $targetDate,
            'total_amount' => 50000,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.orders.export', [
                'start_date' => '2026-10-15',
                'end_date' => '2026-10-15',
            ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename=', (string) $response->headers->get('Content-Disposition'));

        // Cek bahwa output di-stream dan nilai diawali kutip tunggal untuk mitigasi CWE-1236
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // Cek UTF-8 BOM
        $this->assertStringStartsWith(chr(0xEF) . chr(0xBB) . chr(0xBF), $content);
        // Cek sanitasi formula injection (karakter berbahaya diawali tanda petik tunggal)
        $this->assertStringContainsString("'=1+1Malicious", $content);
        $this->assertStringContainsString('Nomor Nota', $content);
    }

    public function test_csv_export_payments_streams_valid_content(): void
    {
        $targetDate = Carbon::parse('2026-10-15 15:00:00');
        $order = Order::factory()->create([
            'order_number' => 'LD-PAY-CSV',
            'customer_id' => $this->customer->id,
            'created_by' => $this->cashier->id,
            'received_at' => $targetDate,
            'total_amount' => 50000,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'recorded_by' => $this->cashier->id,
            'amount' => 50000,
            'method' => PaymentMethod::Tunai,
            'status' => PaymentRecordStatus::Valid,
            'payment_date' => $targetDate,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.payments.export', [
                'start_date' => '2026-10-15',
                'end_date' => '2026-10-15',
            ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('LD-PAY-CSV', $content);
        $this->assertStringContainsString('Tunai', $content);
        $this->assertStringContainsString('50000', $content);
    }
}
