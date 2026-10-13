<?php

namespace Tests\Feature;

use App\Enums\BillingUnit;
use App\Enums\LaundryStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
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

/**
 * Pengujian Komprehensif Task 6.4 — Transaksi Laundry
 * 
 * Memverifikasi 8 parameter pengujian kritis:
 * 1. Uji pesanan dengan satu item.
 * 2. Uji pesanan dengan beberapa item.
 * 3. Uji berat desimal, misalnya 2,5 kg (koma dan titik).
 * 4. Uji kuantitas nol/negatif.
 * 5. Uji diskon dan biaya tambahan (otorisasi kasir vs admin).
 * 6. Uji manipulasi harga melalui browser (Zero Client Trust).
 * 7. Uji nomor transaksi unik dan berurutan.
 * 8. Uji rollback ketika penyimpanan gagal.
 */
class OrderTransactionComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $kasir;
    protected User $admin;
    protected Customer $customer;
    protected Service $serviceKg;
    protected Service $serviceItem;
    protected Service $servicePackage;
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
            'name' => 'Siti Rahmawati',
            'phone' => '081298765432',
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

        $this->servicePackage = Service::factory()->create([
            'name' => 'Paket Jas Lengkap',
            'billing_unit' => BillingUnit::Paket,
            'price' => '50000.00',
            'estimated_hours' => 72,
            'is_active' => true,
        ]);

        BusinessSetting::set(BusinessSetting::KEY_ORDER_NUMBER_PREFIX, 'LD');
        BusinessSetting::set(BusinessSetting::KEY_CASHIER_MAX_DISCOUNT_PERCENT, '20');

        $this->orderService = app(OrderService::class);
    }

    /**
     * 1. Uji pesanan dengan satu item.
     * Verifikasi pembuatan pesanan item tunggal, kalkulasi subtotal, dan snapshot tarif master.
     */
    public function test_1_order_with_single_item_persists_and_calculates_correctly(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'service_id' => $this->serviceItem->id,
                    'quantity' => 1,
                    'notes' => 'Harap disikat bagian kerah',
                ],
            ],
            'discount_amount' => 0,
            'extra_charge' => 0,
        ];

        $response = $this->actingAs($this->kasir)->post(route('orders.store'), $payload);
        $response->assertSessionHasNoErrors();

        /** @var Order $order */
        $order = Order::latest('id')->first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('orders.show', $order));

        $this->assertCount(1, $order->items);
        $this->assertEquals('25000.00', $order->subtotal);
        $this->assertEquals('0.00', $order->discount_amount);
        $this->assertEquals('0.00', $order->extra_charge);
        $this->assertEquals('25000.00', $order->total_amount);
        $this->assertEquals(LaundryStatus::Diterima, $order->laundry_status);
        $this->assertEquals(PaymentStatus::BelumDibayar, $order->payment_status);

        $item = $order->items->first();
        $this->assertEquals($this->serviceItem->id, $item->service_id);
        $this->assertEquals('Cuci Selimut Tebal', $item->service_name_snapshot);
        $this->assertEquals(BillingUnit::Item, $item->billing_unit_snapshot);
        $this->assertEquals('25000.00', $item->unit_price);
        $this->assertEquals('1.00', $item->quantity);
        $this->assertEquals('25000.00', $item->subtotal);
        $this->assertEquals('Harap disikat bagian kerah', $item->notes);
    }

    /**
     * 2. Uji pesanan dengan beberapa item.
     * Verifikasi akumulasi subtotal multi-item dan relasi detail pesanan lengkap.
     */
    public function test_2_order_with_multiple_items_calculates_and_persists_correctly(): void
    {
        // 3 kg Cuci Kering (@ Rp8.000) = Rp24.000
        // 2 pcs Selimut Tebal (@ Rp25.000) = Rp50.000
        // 1 paket Jas Lengkap (@ Rp50.000) = Rp50.000
        // Total subtotal yang diharapkan = Rp124.000
        $payload = [
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'service_id' => $this->serviceKg->id,
                    'quantity' => 3,
                ],
                [
                    'service_id' => $this->serviceItem->id,
                    'quantity' => 2,
                ],
                [
                    'service_id' => $this->servicePackage->id,
                    'quantity' => 1,
                ],
            ],
            'discount_amount' => 0,
            'extra_charge' => 0,
        ];

        $response = $this->actingAs($this->kasir)->post(route('orders.store'), $payload);
        $response->assertSessionHasNoErrors();

        $order = Order::latest('id')->first();
        $this->assertCount(3, $order->items);
        $this->assertEquals('124000.00', $order->subtotal);
        $this->assertEquals('124000.00', $order->total_amount);

        // Verifikasi rincian per baris item
        $itemKg = $order->items->firstWhere('service_id', $this->serviceKg->id);
        $this->assertEquals('3.00', $itemKg->quantity);
        $this->assertEquals('24000.00', $itemKg->subtotal);

        $itemBed = $order->items->firstWhere('service_id', $this->serviceItem->id);
        $this->assertEquals('2.00', $itemBed->quantity);
        $this->assertEquals('50000.00', $itemBed->subtotal);

        $itemPkg = $order->items->firstWhere('service_id', $this->servicePackage->id);
        $this->assertEquals('1.00', $itemPkg->quantity);
        $this->assertEquals('50000.00', $itemPkg->subtotal);
    }

    /**
     * 3. Uji berat desimal, misalnya 2,5 kg (koma dan titik).
     * Memverifikasi normalisasi koma menjadi titik dan perhitungan desimal tanpa distorsi floating-point.
     */
    public function test_3_decimal_weight_quantity_for_example_2_point_5_kg_with_comma_and_dot(): void
    {
        // Uji kasus A: Input pecahan dengan format koma "2,5" (standar input keyboard lokal Indonesia)
        // 2,5 kg x Rp8.000 = Rp20.000
        $payloadComma = [
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'service_id' => $this->serviceKg->id,
                    'quantity' => '2,5',
                ],
            ],
            'discount_amount' => 0,
            'extra_charge' => 0,
        ];

        $responseComma = $this->actingAs($this->kasir)->post(route('orders.store'), $payloadComma);
        $responseComma->assertSessionHasNoErrors();

        $orderA = Order::latest('id')->first();
        $this->assertEquals('20000.00', $orderA->subtotal);
        $this->assertEquals('20000.00', $orderA->total_amount);
        $this->assertEquals('2.50', $orderA->items->first()->quantity);
        $this->assertEquals('20000.00', $orderA->items->first()->subtotal);

        // Uji kasus B: Input pecahan desimal bertingkat "3.75" kg
        // 3.75 kg x Rp8.000 = Rp30.000
        $payloadDot = [
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'service_id' => $this->serviceKg->id,
                    'quantity' => '3.75',
                ],
            ],
            'discount_amount' => 0,
            'extra_charge' => 0,
        ];

        $responseDot = $this->actingAs($this->kasir)->post(route('orders.store'), $payloadDot);
        $responseDot->assertSessionHasNoErrors();

        $orderB = Order::latest('id')->first();
        $this->assertEquals('30000.00', $orderB->subtotal);
        $this->assertEquals('30000.00', $orderB->total_amount);
        $this->assertEquals('3.75', $orderB->items->first()->quantity);
        $this->assertEquals('30000.00', $orderB->items->first()->subtotal);
    }

    /**
     * 4. Uji kuantitas nol/negatif.
     * Memverifikasi penolakan validasi server terhadap kuantitas <= 0 atau non-numerik.
     */
    public function test_4_order_rejects_zero_or_negative_quantity(): void
    {
        // Kasus 1: Kuantitas 0
        $payloadZero = [
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                ['service_id' => $this->serviceKg->id, 'quantity' => 0],
            ],
        ];

        $resZero = $this->actingAs($this->kasir)->post(route('orders.store'), $payloadZero);
        $resZero->assertSessionHasErrors(['items.0.quantity']);

        // Kasus 2: Kuantitas negatif bulat (-5)
        $payloadNeg = [
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                ['service_id' => $this->serviceItem->id, 'quantity' => -5],
            ],
        ];

        $resNeg = $this->actingAs($this->kasir)->post(route('orders.store'), $payloadNeg);
        $resNeg->assertSessionHasErrors(['items.0.quantity']);

        // Kasus 3: Kuantitas negatif desimal (-2.5)
        $payloadNegDec = [
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                ['service_id' => $this->serviceKg->id, 'quantity' => '-2.5'],
            ],
        ];

        $resNegDec = $this->actingAs($this->kasir)->post(route('orders.store'), $payloadNegDec);
        $resNegDec->assertSessionHasErrors(['items.0.quantity']);

        // Pastikan tidak ada data yang masuk ke database
        $this->assertEquals(0, Order::count());
        $this->assertEquals(0, OrderItem::count());
    }

    /**
     * 5. Uji diskon dan biaya tambahan.
     * Verifikasi formula total = subtotal - diskon + extra_charge serta batas otorisasi kasir vs admin.
     */
    public function test_5_order_with_discount_and_extra_charges_and_role_authorization(): void
    {
        // 4 item x Rp25.000 = Rp100.000 subtotal.
        // Kasir menerapkan diskon Rp15.000 (15% <= max 20%) dan biaya express Rp10.000.
        // Total yang diharapkan = Rp100.000 - Rp15.000 + Rp10.000 = Rp95.000.
        $payloadValid = [
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                ['service_id' => $this->serviceItem->id, 'quantity' => 4],
            ],
            'discount_amount' => 15000,
            'extra_charge' => 10000,
        ];

        $responseValid = $this->actingAs($this->kasir)->post(route('orders.store'), $payloadValid);
        $responseValid->assertSessionHasNoErrors();

        $order = Order::latest('id')->first();
        $this->assertEquals('100000.00', $order->subtotal);
        $this->assertEquals('15000.00', $order->discount_amount);
        $this->assertEquals('10000.00', $order->extra_charge);
        $this->assertEquals('95000.00', $order->total_amount);

        // Uji batas otorisasi: Kasir mencoba memberi diskon Rp25.000 (25% > max 20%) -> DITOLAK
        $payloadExcessKasir = [
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                ['service_id' => $this->serviceItem->id, 'quantity' => 4],
            ],
            'discount_amount' => 25000,
            'extra_charge' => 0,
        ];

        $resExcessKasir = $this->actingAs($this->kasir)->post(route('orders.store'), $payloadExcessKasir);
        $resExcessKasir->assertSessionHasErrors(['discount_amount']);

        // Uji Admin: Admin diperbolehkan memberikan diskon lebih tinggi (misal Rp50.000 / 50%) -> DITERIMA
        $payloadAdmin = [
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                ['service_id' => $this->serviceItem->id, 'quantity' => 4],
            ],
            'discount_amount' => 50000,
            'extra_charge' => 0,
        ];

        $resAdmin = $this->actingAs($this->admin)->post(route('orders.store'), $payloadAdmin);
        $resAdmin->assertSessionHasNoErrors();

        $orderAdmin = Order::latest('id')->first();
        $this->assertEquals('50000.00', $orderAdmin->discount_amount);
        $this->assertEquals('50000.00', $orderAdmin->total_amount);
    }

    /**
     * 6. Uji manipulasi harga melalui browser (Zero Client Trust).
     * Server mengabaikan total dan harga yang diubah pada payload dan tetap mengambil tarif master dari database.
     */
    public function test_6_order_ignores_browser_price_tampering(): void
    {
        // Percobaan manipulasi payload oleh penyerang melalui DevTools:
        // Harga diset Rp1 per kg dan subtotal diset Rp3 untuk 3 kg.
        $payloadTampered = [
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'service_id' => $this->serviceKg->id,
                    'quantity' => 3,
                    'unit_price' => '1.00',     // FAKE PRICE
                    'subtotal' => '3.00',       // FAKE SUBTOTAL
                ],
            ],
            'discount_amount' => 0,
            'extra_charge' => 0,
            'total_amount' => '3.00',           // FAKE TOTAL
        ];

        $response = $this->actingAs($this->kasir)->post(route('orders.store'), $payloadTampered);
        $response->assertSessionHasNoErrors();

        $order = Order::latest('id')->first();
        // Server wajib menghitung berdasarkan tarif resmi master (Rp8.000 x 3 = Rp24.000)
        $this->assertEquals('24000.00', $order->subtotal);
        $this->assertEquals('24000.00', $order->total_amount);

        $item = $order->items->first();
        $this->assertEquals('8000.00', $item->unit_price);
        $this->assertEquals('24000.00', $item->subtotal);
    }

    /**
     * 7. Uji nomor transaksi unik dan berurutan.
     * Nomor nota dibuat dengan pola LD-YYYYMMDD-0001, LD-YYYYMMDD-0002 tanpa tabrakan.
     */
    public function test_7_unique_and_sequential_transaction_numbers(): void
    {
        $todayPrefix = 'LD-' . now()->format('Ymd') . '-';

        $order1 = $this->orderService->createOrder([
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                ['service_id' => $this->serviceKg->id, 'quantity' => 1],
            ],
            'discount_amount' => 0,
            'extra_charge' => 0,
        ], $this->kasir);

        $order2 = $this->orderService->createOrder([
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                ['service_id' => $this->serviceKg->id, 'quantity' => 2],
            ],
            'discount_amount' => 0,
            'extra_charge' => 0,
        ], $this->kasir);

        $order3 = $this->orderService->createOrder([
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                ['service_id' => $this->serviceKg->id, 'quantity' => 3],
            ],
            'discount_amount' => 0,
            'extra_charge' => 0,
        ], $this->kasir);

        $this->assertEquals($todayPrefix . '0001', $order1->order_number);
        $this->assertEquals($todayPrefix . '0002', $order2->order_number);
        $this->assertEquals($todayPrefix . '0003', $order3->order_number);

        // Pastikan tidak ada duplikasi nomor nota di seluruh database
        $this->assertEquals(3, Order::distinct('order_number')->count());
    }

    /**
     * 8. Uji rollback ketika penyimpanan gagal.
     * Memastikan database transaction membatalkan seluruh operasi jika terjadi pengecualian saat proses simpan item.
     */
    public function test_8_database_transaction_rolls_back_entire_order_if_any_item_fails(): void
    {
        $this->assertEquals(0, Order::count());
        $this->assertEquals(0, OrderItem::count());

        $failedService = Service::factory()->create([
            'name' => 'Layanan Error Test',
            'billing_unit' => BillingUnit::Kg,
            'price' => '10000.00',
            'is_active' => true,
        ]);

        // Mock OrderItem saving untuk memicu exception pada item tertentu
        OrderItem::saving(function ($item) use ($failedService) {
            if ($item->service_id === $failedService->id) {
                throw new Exception('Simulasi kegagalan database pada baris item');
            }
        });

        $payload = [
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                ['service_id' => $this->serviceKg->id, 'quantity' => 2],
                ['service_id' => $failedService->id, 'quantity' => 1], // Memicu rollback
            ],
            'discount_amount' => 0,
            'extra_charge' => 0,
        ];

        try {
            $this->orderService->createOrder($payload, $this->kasir);
            $this->fail('Seharusnya melempar Exception.');
        } catch (Exception $e) {
            $this->assertStringContainsString('Simulasi kegagalan database', $e->getMessage());
        }

        // Verifikasi atomisitas: Seluruh transaksi dibatalkan, tidak ada data order atau item yatim tersisa di database
        $this->assertEquals(0, Order::count());
        $this->assertEquals(0, OrderItem::count());
        $this->assertEquals(0, OrderStatusHistory::count());
    }
}
