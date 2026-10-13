<?php

namespace Tests\Feature;

use App\Enums\LaundryStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderSearchAndFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $cashier;
    private User $otherCashier;
    private Customer $customerA;
    private Customer $customerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create([
            'role' => UserRole::Kasir,
            'name' => 'Budi Kasir',
            'is_active' => true,
        ]);

        $this->otherCashier = User::factory()->create([
            'role' => UserRole::Kasir,
            'name' => 'Siti Kasir',
            'is_active' => true,
        ]);

        $this->customerA = Customer::factory()->create([
            'name' => 'Ahmad Dahlan',
            'phone' => '081234567890',
        ]);

        $this->customerB = Customer::factory()->create([
            'name' => 'Bambang Pamungkas',
            'phone' => '089876543210',
        ]);
    }

    public function test_guest_is_redirected_to_login_when_accessing_orders_index(): void
    {
        $this->get(route('orders.index'))
            ->assertRedirect(route('login'));
    }

    public function test_cashier_and_admin_can_view_orders_index(): void
    {
        $this->actingAs($this->cashier)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertViewIs('orders.index');

        $this->actingAs($this->admin)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertViewIs('orders.index');
    }

    public function test_orders_list_is_paginated_to_fifteen_items(): void
    {
        Order::factory()->count(20)->create([
            'customer_id' => $this->customerA->id,
            'created_by' => $this->cashier->id,
        ]);

        $response = $this->actingAs($this->cashier)
            ->get(route('orders.index'));

        $response->assertOk();
        $paginator = $response->viewData('orders');
        $this->assertSame(15, $paginator->perPage());
        $this->assertSame(20, $paginator->total());
        $this->assertSame(2, $paginator->lastPage());
    }

    public function test_search_by_exact_order_number(): void
    {
        $targetOrder = Order::factory()->create([
            'order_number' => 'LD-20261013-0001',
            'customer_id' => $this->customerA->id,
            'created_by' => $this->cashier->id,
        ]);

        $otherOrder = Order::factory()->create([
            'order_number' => 'LD-20261013-0002',
            'customer_id' => $this->customerB->id,
            'created_by' => $this->cashier->id,
        ]);

        $response = $this->actingAs($this->cashier)
            ->get(route('orders.index', ['q' => '0001']));

        $response->assertOk();
        $response->assertSee('LD-20261013-0001');
        $response->assertDontSee('LD-20261013-0002');
    }

    public function test_search_by_customer_name_and_phone(): void
    {
        $orderA = Order::factory()->create([
            'order_number' => 'LD-20261013-0010',
            'customer_id' => $this->customerA->id, // Ahmad Dahlan, 081234567890
            'created_by' => $this->cashier->id,
        ]);

        $orderB = Order::factory()->create([
            'order_number' => 'LD-20261013-0020',
            'customer_id' => $this->customerB->id, // Bambang Pamungkas, 089876543210
            'created_by' => $this->cashier->id,
        ]);

        // Cari berdasarkan nama
        $resName = $this->actingAs($this->cashier)
            ->get(route('orders.index', ['q' => 'Dahlan']));
        $resName->assertSee('LD-20261013-0010');
        $resName->assertDontSee('LD-20261013-0020');

        // Cari berdasarkan nomor telepon
        $resPhone = $this->actingAs($this->cashier)
            ->get(route('orders.index', ['q' => '987654']));
        $resPhone->assertSee('LD-20261013-0020');
        $resPhone->assertDontSee('LD-20261013-0010');
    }

    public function test_filter_by_date_range(): void
    {
        $orderPast = Order::factory()->create([
            'order_number' => 'LD-PAST',
            'customer_id' => $this->customerA->id,
            'created_by' => $this->cashier->id,
            'received_at' => Carbon::parse('2026-10-01 10:00:00'),
        ]);

        $orderTarget = Order::factory()->create([
            'order_number' => 'LD-TARGET',
            'customer_id' => $this->customerA->id,
            'created_by' => $this->cashier->id,
            'received_at' => Carbon::parse('2026-10-05 14:00:00'),
        ]);

        $orderFuture = Order::factory()->create([
            'order_number' => 'LD-FUTURE',
            'customer_id' => $this->customerA->id,
            'created_by' => $this->cashier->id,
            'received_at' => Carbon::parse('2026-10-10 09:00:00'),
        ]);

        $response = $this->actingAs($this->cashier)
            ->get(route('orders.index', [
                'date_from' => '2026-10-04',
                'date_to' => '2026-10-06',
            ]));

        $response->assertOk();
        $response->assertSee('LD-TARGET');
        $response->assertDontSee('LD-PAST');
        $response->assertDontSee('LD-FUTURE');
    }

    public function test_filter_by_laundry_status_and_payment_status(): void
    {
        $order1 = Order::factory()->create([
            'order_number' => 'LD-CUCI-LUNAS',
            'customer_id' => $this->customerA->id,
            'created_by' => $this->cashier->id,
            'laundry_status' => LaundryStatus::Dicuci,
            'payment_status' => PaymentStatus::Lunas,
        ]);

        $order2 = Order::factory()->create([
            'order_number' => 'LD-CUCI-BELUM',
            'customer_id' => $this->customerA->id,
            'created_by' => $this->cashier->id,
            'laundry_status' => LaundryStatus::Dicuci,
            'payment_status' => PaymentStatus::BelumDibayar,
        ]);

        $order3 = Order::factory()->create([
            'order_number' => 'LD-SELESAI-LUNAS',
            'customer_id' => $this->customerA->id,
            'created_by' => $this->cashier->id,
            'laundry_status' => LaundryStatus::Selesai,
            'payment_status' => PaymentStatus::Lunas,
        ]);

        // Filter status cucian: Dicuci
        $resLaundry = $this->actingAs($this->cashier)
            ->get(route('orders.index', ['laundry_status' => LaundryStatus::Dicuci->value]));
        $resLaundry->assertSee('LD-CUCI-LUNAS');
        $resLaundry->assertSee('LD-CUCI-BELUM');
        $resLaundry->assertDontSee('LD-SELESAI-LUNAS');

        // Filter kombinasi status cucian: Dicuci AND status pembayaran: Lunas
        $resCombined = $this->actingAs($this->cashier)
            ->get(route('orders.index', [
                'laundry_status' => LaundryStatus::Dicuci->value,
                'payment_status' => PaymentStatus::Lunas->value,
            ]));
        $resCombined->assertSee('LD-CUCI-LUNAS');
        $resCombined->assertDontSee('LD-CUCI-BELUM');
        $resCombined->assertDontSee('LD-SELESAI-LUNAS');
    }

    public function test_filter_by_cashier_operator(): void
    {
        $orderBudi = Order::factory()->create([
            'order_number' => 'LD-BUDI',
            'customer_id' => $this->customerA->id,
            'created_by' => $this->cashier->id,
        ]);

        $orderSiti = Order::factory()->create([
            'order_number' => 'LD-SITI',
            'customer_id' => $this->customerB->id,
            'created_by' => $this->otherCashier->id,
        ]);

        $response = $this->actingAs($this->cashier)
            ->get(route('orders.index', ['user_id' => $this->cashier->id]));

        $response->assertOk();
        $response->assertSee('LD-BUDI');
        $response->assertDontSee('LD-SITI');
    }

    public function test_pagination_preserves_query_filters_in_links(): void
    {
        Order::factory()->count(25)->create([
            'customer_id' => $this->customerA->id,
            'created_by' => $this->cashier->id,
            'laundry_status' => LaundryStatus::MenungguProses,
        ]);

        $response = $this->actingAs($this->cashier)
            ->get(route('orders.index', [
                'laundry_status' => LaundryStatus::MenungguProses->value,
                'q' => 'Ahmad',
            ]));

        $response->assertOk();
        // Memastikan pagination link menyertakan query parameter filter
        $response->assertSee('laundry_status=menunggu_proses');
        $response->assertSee('q=Ahmad');
    }
}
