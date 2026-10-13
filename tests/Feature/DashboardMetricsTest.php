<?php

namespace Tests\Feature;

use App\Enums\LaundryStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
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

    public function test_guest_is_redirected_to_login_when_accessing_dashboard(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_both_admin_and_cashier_can_access_dashboard(): void
    {
        $this->actingAs($this->cashier)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard.index')
            ->assertViewHasAll(['metrics', 'recentOrders', 'weeklyTrend', 'statusDistribution']);

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard.index');
    }

    public function test_dashboard_metrics_count_orders_today_accurately(): void
    {
        // 2 pesanan hari ini
        Order::factory()->count(2)->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->cashier->id,
            'received_at' => Carbon::today()->addHours(2),
        ]);

        // 1 pesanan kemarin
        Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->cashier->id,
            'received_at' => Carbon::yesterday(),
        ]);

        $service = app(DashboardService::class);
        $metrics = $service->getMetrics();

        $this->assertSame(2, $metrics['orders_today_count']);
    }

    public function test_dashboard_metrics_count_processing_ready_and_completed_orders(): void
    {
        // Status Menunggu Proses & Dicuci (Sedang diproses = 2)
        Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->cashier->id,
            'laundry_status' => LaundryStatus::MenungguProses,
        ]);
        Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->cashier->id,
            'laundry_status' => LaundryStatus::Dicuci,
        ]);

        // Status Siap Diambil = 1
        Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->cashier->id,
            'laundry_status' => LaundryStatus::SiapDiambil,
        ]);

        // Status Selesai = 2 (1 selesai hari ini, 1 kemarin)
        Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->cashier->id,
            'laundry_status' => LaundryStatus::Selesai,
            'completed_at' => Carbon::today(),
        ]);
        Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->cashier->id,
            'laundry_status' => LaundryStatus::Selesai,
            'completed_at' => Carbon::yesterday(),
        ]);

        $service = app(DashboardService::class);
        $metrics = $service->getMetrics();

        $this->assertSame(2, $metrics['processing_orders_count']);
        $this->assertSame(1, $metrics['ready_orders_count']);
        $this->assertSame(2, $metrics['completed_orders_count']);
        $this->assertSame(1, $metrics['completed_today_count']);
    }

    public function test_dashboard_cash_inflow_today_strictly_excludes_voided_and_past_payments(): void
    {
        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->cashier->id,
            'total_amount' => 150000,
        ]);

        // 1. Pembayaran valid hari ini: Rp50.000 (HARUS dihitung)
        Payment::factory()->create([
            'order_id' => $order->id,
            'recorded_by' => $this->cashier->id,
            'amount' => 50000,
            'status' => PaymentRecordStatus::Valid,
            'payment_date' => Carbon::today()->addHours(2),
        ]);

        // 2. Pembayaran hari ini tapi dibatalkan (voided): Rp30.000 (TIDAK BOLEH dihitung)
        Payment::factory()->create([
            'order_id' => $order->id,
            'recorded_by' => $this->cashier->id,
            'amount' => 30000,
            'status' => PaymentRecordStatus::Voided,
            'payment_date' => Carbon::today()->addHours(3),
        ]);

        // 3. Pembayaran valid kemarin: Rp40.000 (TIDAK BOLEH dihitung hari ini)
        Payment::factory()->create([
            'order_id' => $order->id,
            'recorded_by' => $this->cashier->id,
            'amount' => 40000,
            'status' => PaymentRecordStatus::Valid,
            'payment_date' => Carbon::yesterday(),
        ]);

        $service = app(DashboardService::class);
        $metrics = $service->getMetrics();

        // Hanya pembayaran valid hari ini (Rp50.000)
        $this->assertEquals(50000.0, $metrics['cash_inflow_today']);
    }

    public function test_dashboard_calculates_total_unpaid_balance_accurately(): void
    {
        // Order 1: Total 100.000, dibayar valid 30.000 -> Sisa 70.000
        $order1 = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->cashier->id,
            'total_amount' => 100000,
            'payment_status' => PaymentStatus::DibayarSebagian,
            'laundry_status' => LaundryStatus::Dicuci,
        ]);
        Payment::factory()->create([
            'order_id' => $order1->id,
            'recorded_by' => $this->cashier->id,
            'amount' => 30000,
            'status' => PaymentRecordStatus::Valid,
            'payment_date' => Carbon::today(),
        ]);

        // Order 2: Total 50.000, belum bayar sama sekali -> Sisa 50.000
        Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->cashier->id,
            'total_amount' => 50000,
            'payment_status' => PaymentStatus::BelumDibayar,
            'laundry_status' => LaundryStatus::MenungguProses,
        ]);

        // Order 3: Total 80.000, lunas dibayar 80.000 -> Sisa 0 (tidak menambah piutang)
        $order3 = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->cashier->id,
            'total_amount' => 80000,
            'payment_status' => PaymentStatus::Lunas,
            'laundry_status' => LaundryStatus::Selesai,
        ]);
        Payment::factory()->create([
            'order_id' => $order3->id,
            'recorded_by' => $this->cashier->id,
            'amount' => 80000,
            'status' => PaymentRecordStatus::Valid,
            'payment_date' => Carbon::today(),
        ]);

        // Order 4: Dibatalkan total 60.000 -> TIDAK BOLEH dihitung sebagai piutang aktif
        Order::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->cashier->id,
            'total_amount' => 60000,
            'payment_status' => PaymentStatus::BelumDibayar,
            'laundry_status' => LaundryStatus::Dibatalkan,
        ]);

        $service = app(DashboardService::class);
        $metrics = $service->getMetrics();

        // 70.000 (Order 1) + 50.000 (Order 2) = 120.000
        $this->assertEquals(120000.0, $metrics['total_unpaid_balance']);
    }

    public function test_dashboard_weekly_trend_returns_seven_days(): void
    {
        $service = app(DashboardService::class);
        $trend = $service->getWeeklyTrend();

        $this->assertCount(7, $trend);
        $this->assertTrue($trend->last()['is_today']);
        $this->assertArrayHasKey('orders_count', $trend->first());
        $this->assertArrayHasKey('cash_amount', $trend->first());
    }

    public function test_dashboard_renders_ten_recent_orders_in_html(): void
    {
        $orders = Order::factory()->count(12)->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->cashier->id,
            'received_at' => now(),
        ]);

        $response = $this->actingAs($this->cashier)
            ->get(route('dashboard'));

        $response->assertOk();
        // Memastikan 10 pesanan teratas muncul
        $this->assertCount(10, $response->viewData('recentOrders'));
    }
}
