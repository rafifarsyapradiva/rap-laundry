<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Enums\BillingUnit;
use App\Enums\LaundryStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Service;
use App\Models\User;
use App\Services\OrderService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderCalculationAndPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected User $kasir;
    protected User $admin;
    protected Customer $customer;
    protected Service $serviceKg;
    protected Service $serviceItem;
    protected OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kasir = User::factory()->create([
            'role' => UserRole::Kasir,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->customer = Customer::factory()->create([
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        $this->serviceKg = Service::factory()->create([
            'name' => 'Cuci Kering Kiloan',
            'billing_unit' => BillingUnit::Kg,
            'price' => '8000.00',
            'estimated_hours' => 24,
            'is_active' => true,
        ]);

        $this->serviceItem = Service::factory()->create([
            'name' => 'Cuci Selimut Tebal',
            'billing_unit' => BillingUnit::Item,
            'price' => '25000.00',
            'estimated_hours' => 48,
            'is_active' => true,
        ]);

        BusinessSetting::set(BusinessSetting::KEY_ORDER_NUMBER_PREFIX, 'LD');
        BusinessSetting::set(BusinessSetting::KEY_CASHIER_MAX_DISCOUNT_PERCENT, '20');

        $this->orderService = app(OrderService::class);
    }

    /**
     * Subtotal setiap item dan subtotal pesanan dihitung ulang di server dari harga master database.
     * Nilai harga palsu yang dikirim dari browser diabaikan.
     */
    public function test_order_subtotal_is_accurately_calculated_from_server_master_prices(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'received_at' => '2026-10-13 10:00:00',
            'items' => [
                [
                    'service_id' => $this->serviceKg->id,
                    'quantity' => 3,
                    'unit_price' => 100, // Nilai manipulasi client browser (seharusnya Rp8.000)
                    'subtotal' => 300,   // Nilai manipulasi client browser
                ],
                [
                    'service_id' => $this->serviceItem->id,
                    'quantity' => 2,
                    'unit_price' => 500, // Nilai manipulasi client browser (seharusnya Rp25.000)
                    'subtotal' => 1000,
                ],
            ],
            'discount_amount' => 0,
            'extra_charge' => 0,
        ];

        $response = $this->actingAs($this->kasir)->post(route('orders.store'), $payload);
        $response->assertSessionHasNoErrors();

        /** @var Order $order */
        $order = Order::latest()->first();
        $this->assertNotNull($order);

        // 3 kg x 8.000 = 24.000
        // 2 item x 25.000 = 50.000
        // Total subtotal = 74.000 (BUKAN 1.300 manipulasi browser)
        $this->assertEquals('74000.00', $order->subtotal);
        $this->assertEquals('74000.00', $order->total_amount);

        $this->assertCount(2, $order->items);
        $item1 = $order->items->firstWhere('service_id', $this->serviceKg->id);
        $this->assertEquals('8000.00', $item1->unit_price);
        $this->assertEquals('24000.00', $item1->subtotal);

        $item2 = $order->items->firstWhere('service_id', $this->serviceItem->id);
        $this->assertEquals('25000.00', $item2->unit_price);
        $this->assertEquals('50000.00', $item2->subtotal);
    }

    /**
     * Total pesanan dihitung akurat dengan rumus: subtotal - diskon + biaya tambahan.
     */
    public function test_order_total_is_correctly_calculated_with_discount_and_extra_charge(): void
    {
        // 2 item x 25.000 = 50.000 subtotal. Diskon: 5.000. Extra charge: 10.000.
        // Total = 50.000 - 5.000 + 10.000 = 55.000.
        $payload = [
            'customer_id' => $this->customer->id,
            'received_at' => '2026-10-13 11:00:00',
            'items' => [
                [
                    'service_id' => $this->serviceItem->id,
                    'quantity' => 2,
                ],
            ],
            'discount_amount' => 5000,
            'extra_charge' => 10000,
        ];

        $response = $this->actingAs($this->kasir)->post(route('orders.store'), $payload);
        $response->assertSessionHasNoErrors();

        $order = Order::latest()->first();
        $this->assertEquals('50000.00', $order->subtotal);
        $this->assertEquals('5000.00', $order->discount_amount);
        $this->assertEquals('10000.00', $order->extra_charge);
        $this->assertEquals('55000.00', $order->total_amount);
    }

    /**
     * Kuantitas pecahan desimal kilogram dihitung akurat tanpa kesalahan floating-point.
     */
    public function test_decimal_weight_quantity_calculates_precise_subtotal(): void
    {
        // 2.75 kg x 8.000 = 22.000
        $payload = [
            'customer_id' => $this->customer->id,
            'received_at' => '2026-10-13 12:00:00',
            'items' => [
                [
                    'service_id' => $this->serviceKg->id,
                    'quantity' => '2.75',
                ],
            ],
            'discount_amount' => 0,
            'extra_charge' => 0,
        ];

        $response = $this->actingAs($this->kasir)->post(route('orders.store'), $payload);
        $response->assertSessionHasNoErrors();

        $order = Order::latest()->first();
        $this->assertEquals('22000.00', $order->subtotal);
        $this->assertEquals('22000.00', $order->total_amount);

        $item = $order->items->first();
        $this->assertEquals('2.75', $item->quantity);
        $this->assertEquals('22000.00', $item->subtotal);
    }

    /**
     * Snapshot nama layanan, satuan penagihan, dan tarif satuan tersimpan permanen pada order_items.
     */
    public function test_snapshots_of_service_name_unit_and_price_are_persisted_correctly(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'received_at' => '2026-10-13 13:00:00',
            'items' => [
                [
                    'service_id' => $this->serviceKg->id,
                    'quantity' => 5,
                    'notes' => 'Harap disetrika halus',
                ],
            ],
        ];

        $this->actingAs($this->kasir)->post(route('orders.store'), $payload);

        $order = Order::latest()->first();
        $item = $order->items->first();

        $this->assertEquals('Cuci Kering Kiloan', $item->service_name_snapshot);
        $this->assertEquals(BillingUnit::Kg, $item->billing_unit_snapshot);
        $this->assertEquals('8000.00', $item->unit_price);
        $this->assertEquals('Harap disetrika halus', $item->notes);
    }

    /**
     * Integritas snapshot finansial: Perubahan tarif master layanan di masa depan tidak mengubah nota order historis.
     */
    public function test_future_service_price_change_does_not_mutate_existing_order_snapshot(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'received_at' => '2026-10-13 14:00:00',
            'items' => [
                [
                    'service_id' => $this->serviceKg->id,
                    'quantity' => 10, // 10 x 8.000 = 80.000
                ],
            ],
        ];

        $this->actingAs($this->kasir)->post(route('orders.store'), $payload);

        $order = Order::latest()->first();
        $this->assertEquals('80000.00', $order->total_amount);

        // Administrator mengubah harga master layanan dari 8.000 menjadi 15.000 dan nama layanan
        $this->serviceKg->update([
            'name' => 'Cuci Kering Premium VIP',
            'price' => '15000.00',
        ]);

        // Muat ulang order item dari database
        $item = OrderItem::where('order_id', $order->id)->first();
        $order->refresh();

        $this->assertEquals('Cuci Kering Kiloan', $item->service_name_snapshot);
        $this->assertEquals('8000.00', $item->unit_price);
        $this->assertEquals('80000.00', $item->subtotal);
        $this->assertEquals('80000.00', $order->total_amount);
    }

    /**
     * Atomisitas Database Transaction: Kegagalan menyimpan salah satu item membatalkan seluruh transaksi database (rollback).
     */
    public function test_database_transaction_rolls_back_entire_order_if_any_item_fails(): void
    {
        $initialOrderCount = Order::count();
        $initialItemCount = OrderItem::count();
        $initialHistoryCount = OrderStatusHistory::count();

        // Buat payload dengan item kedua yang memiliki service_id tidak valid
        $failingData = [
            'customer_id' => $this->customer->id,
            'received_at' => '2026-10-13 15:00:00',
            'items' => [
                [
                    'service_id' => $this->serviceKg->id,
                    'quantity' => 2,
                ],
                [
                    'service_id' => 999999, // Tidak ada di database
                    'quantity' => 1,
                ],
            ],
            'discount_amount' => 0,
            'extra_charge' => 0,
        ];

        try {
            $this->orderService->createOrder($failingData, $this->kasir);
            $this->fail('Harus melempar exception karena service tidak valid');
        } catch (Exception $e) {
            // Exception berhasil ditangkap
        }

        // Verifikasi tidak ada data parsial yang tertinggal di database
        $this->assertEquals($initialOrderCount, Order::count());
        $this->assertEquals($initialItemCount, OrderItem::count());
        $this->assertEquals($initialHistoryCount, OrderStatusHistory::count());
    }

    /**
     * Nomor transaksi unik dibuat berurutan dengan format LD-YYYYMMDD-0001, LD-YYYYMMDD-0002.
     */
    public function test_order_number_is_sequentially_generated_with_daily_period(): void
    {
        $payload1 = [
            'customer_id' => $this->customer->id,
            'received_at' => '2026-10-13 08:00:00',
            'items' => [
                ['service_id' => $this->serviceKg->id, 'quantity' => 2],
            ],
        ];

        $payload2 = [
            'customer_id' => $this->customer->id,
            'received_at' => '2026-10-13 09:00:00',
            'items' => [
                ['service_id' => $this->serviceKg->id, 'quantity' => 3],
            ],
        ];

        $this->actingAs($this->kasir)->post(route('orders.store'), $payload1);
        $this->actingAs($this->kasir)->post(route('orders.store'), $payload2);

        $orders = Order::orderBy('id')->get();
        $this->assertCount(2, $orders);

        $this->assertEquals('LD-20261013-0001', $orders[0]->order_number);
        $this->assertEquals('LD-20261013-0002', $orders[1]->order_number);
    }

    /**
     * Awalan nomor transaksi menghormati konfigurasi pada business_settings.
     */
    public function test_order_number_prefix_respects_business_settings(): void
    {
        BusinessSetting::set(BusinessSetting::KEY_ORDER_NUMBER_PREFIX, 'RAP');

        $payload = [
            'customer_id' => $this->customer->id,
            'received_at' => '2026-10-13 10:00:00',
            'items' => [
                ['service_id' => $this->serviceKg->id, 'quantity' => 1],
            ],
        ];

        $this->actingAs($this->kasir)->post(route('orders.store'), $payload);

        $order = Order::latest()->first();
        $this->assertStringStartsWith('RAP-20261013-', $order->order_number);
    }

    /**
     * Riwayat status awal pesanan tersimpan secara otomatis dengan status Diterima.
     */
    public function test_initial_order_status_history_is_recorded_with_diterima_status(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'received_at' => '2026-10-13 10:00:00',
            'items' => [
                ['service_id' => $this->serviceKg->id, 'quantity' => 2],
            ],
        ];

        $this->actingAs($this->kasir)->post(route('orders.store'), $payload);

        $order = Order::latest()->first();
        $this->assertEquals(LaundryStatus::Diterima, $order->laundry_status);
        $this->assertEquals(PaymentStatus::BelumDibayar, $order->payment_status);

        $this->assertCount(1, $order->statusHistories);
        $history = $order->statusHistories->first();
        $this->assertNull($history->from_status);
        $this->assertEquals(LaundryStatus::Diterima, $history->to_status);
        $this->assertEquals($this->kasir->id, $history->changed_by);
    }

    /**
     * Aktivitas pencatatan pesanan baru terekam ke tabel activity_logs.
     */
    public function test_activity_log_is_recorded_for_order_creation(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'received_at' => '2026-10-13 10:00:00',
            'items' => [
                ['service_id' => $this->serviceKg->id, 'quantity' => 2],
            ],
        ];

        $this->actingAs($this->kasir)->post(route('orders.store'), $payload);

        $order = Order::latest()->first();

        $log = ActivityLog::where('action', ActivityAction::OrderCreated)
            ->where('subject_id', $order->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($this->kasir->id, $log->user_id);
        $this->assertEquals($order->order_number, $log->metadata['order_number']);
    }

    /**
     * Pesanan dengan satu item tunggal tersimpan dan dihitung secara tepat.
     */
    public function test_order_with_single_item_calculates_and_persists_correctly(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'received_at' => '2026-10-13 14:00:00',
            'items' => [
                [
                    'service_id' => $this->serviceItem->id,
                    'quantity' => 1,
                    'notes' => 'Harap jangan dicampur',
                ],
            ],
            'discount_amount' => 0,
            'extra_charge' => 0,
        ];

        $response = $this->actingAs($this->kasir)->post(route('orders.store'), $payload);
        $response->assertSessionHasNoErrors();

        $order = Order::latest()->first();
        $this->assertNotNull($order);
        $this->assertCount(1, $order->items);
        $this->assertEquals('25000.00', $order->subtotal);
        $this->assertEquals('25000.00', $order->total_amount);

        $item = $order->items->first();
        $this->assertEquals('1.00', $item->quantity);
        $this->assertEquals('25000.00', $item->unit_price);
        $this->assertEquals('25000.00', $item->subtotal);
        $this->assertEquals('Harap jangan dicampur', $item->notes);
    }
}
