<?php

namespace Tests\Feature;

use App\Enums\BillingUnit;
use App\Enums\UserRole;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFormValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $kasir;
    protected Customer $customer;
    protected Service $serviceKg;
    protected Service $serviceItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->kasir = User::factory()->create([
            'role' => UserRole::Kasir,
            'is_active' => true,
        ]);

        $this->customer = Customer::factory()->create([
            'name' => 'Pelanggan Setia',
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
            'name' => 'Cuci Bed Cover Satuan',
            'billing_unit' => BillingUnit::Item,
            'price' => '25000.00',
            'estimated_hours' => 48,
            'is_active' => true,
        ]);

        BusinessSetting::set(BusinessSetting::KEY_CASHIER_MAX_DISCOUNT_PERCENT, '20');
        BusinessSetting::set(BusinessSetting::KEY_DEFAULT_ESTIMATED_HOURS, '48');
    }

    /**
     * Tamu (belum login) ditolak dan dialihkan ke login.
     */
    public function test_guest_cannot_access_order_create_form(): void
    {
        $response = $this->get(route('orders.create'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Staf kasir dan admin dapat mengakses halaman formulir pembuatan pesanan baru.
     */
    public function test_cashier_and_admin_can_access_order_create_form(): void
    {
        $responseKasir = $this->actingAs($this->kasir)->get(route('orders.create'));
        $responseKasir->assertOk();
        $responseKasir->assertSee('Buat Pesanan Laundry Baru');
        $responseKasir->assertSee($this->customer->name);
        $responseKasir->assertSee($this->serviceKg->name);

        $responseAdmin = $this->actingAs($this->admin)->get(route('orders.create'));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee('Buat Pesanan Laundry Baru');
    }

    /**
     * Form pesanan menolak jika tidak ada baris item layanan yang disertakan.
     */
    public function test_order_form_rejects_empty_items(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [],
        ];

        $response = $this->actingAs($this->kasir)->post(route('orders.store'), $payload);
        $response->assertSessionHasErrors(['items']);
    }

    /**
     * Form pesanan memvalidasi kuantitas harus bernilai lebih besar dari nol.
     */
    public function test_order_form_rejects_zero_or_negative_quantity(): void
    {
        $zeroPayload = [
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'service_id' => $this->serviceKg->id,
                    'quantity' => 0,
                ],
            ],
        ];

        $responseZero = $this->actingAs($this->kasir)->post(route('orders.store'), $zeroPayload);
        $responseZero->assertSessionHasErrors(['items.0.quantity']);

        $negPayload = [
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'service_id' => $this->serviceKg->id,
                    'quantity' => -2.5,
                ],
            ],
        ];

        $responseNeg = $this->actingAs($this->kasir)->post(route('orders.store'), $negPayload);
        $responseNeg->assertSessionHasErrors(['items.0.quantity']);
    }

    /**
     * Form pesanan menerima kuantitas desimal untuk layanan kilogram (misal 2.5 atau format lokal 2,5).
     */
    public function test_order_form_accepts_decimal_quantity_for_kg_service(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'service_id' => $this->serviceKg->id,
                    'quantity' => '2,75', // Format koma Indonesia
                ],
            ],
            'discount_amount' => 0,
            'extra_charge' => 0,
        ];

        $response = $this->actingAs($this->kasir)->post(route('orders.store'), $payload);
        $response->assertSessionHasNoErrors();
    }

    /**
     * Form pesanan menolak kuantitas pecahan desimal untuk layanan berbasis item/satuan.
     */
    public function test_order_form_rejects_decimal_quantity_for_item_service(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'service_id' => $this->serviceItem->id,
                    'quantity' => 1.5, // 1.5 selimut tidak valid (harus bulat)
                ],
            ],
        ];

        $response = $this->actingAs($this->kasir)->post(route('orders.store'), $payload);
        $response->assertSessionHasErrors(['items.0.quantity']);
    }

    /**
     * Form pesanan menolak diskon yang melampaui total subtotal pesanan.
     */
    public function test_order_form_rejects_discount_exceeding_subtotal(): void
    {
        // 2 item x 25.000 = 50.000 subtotal. Diskon 60.000 harus ditolak.
        $payload = [
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'service_id' => $this->serviceItem->id,
                    'quantity' => 2,
                ],
            ],
            'discount_amount' => 60000,
        ];

        $response = $this->actingAs($this->admin)->post(route('orders.store'), $payload);
        $response->assertSessionHasErrors(['discount_amount']);
    }

    /**
     * Kasir ditolak jika memberikan diskon melebihi persentase yang diizinkan (20%).
     */
    public function test_cashier_cannot_exceed_max_discount_percentage(): void
    {
        // Subtotal = 100.000 (4 x 25.000). Batas kasir 20% = 20.000.
        // Jika kasir input 25.000 (25%), harus ditolak.
        $payloadExceed = [
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'service_id' => $this->serviceItem->id,
                    'quantity' => 4,
                ],
            ],
            'discount_amount' => 25000,
        ];

        $responseExceed = $this->actingAs($this->kasir)->post(route('orders.store'), $payloadExceed);
        $responseExceed->assertSessionHasErrors(['discount_amount']);

        // Jika kasir input 20.000 (20%), harus lolos.
        $payloadAllowed = [
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'service_id' => $this->serviceItem->id,
                    'quantity' => 4,
                ],
            ],
            'discount_amount' => 20000,
        ];

        $responseAllowed = $this->actingAs($this->kasir)->post(route('orders.store'), $payloadAllowed);
        $responseAllowed->assertSessionHasNoErrors();
    }

    /**
     * Admin berwenang memberikan diskon lebih besar (misal 50% atau hingga 100% subtotal).
     */
    public function test_admin_can_apply_full_discount_up_to_subtotal(): void
    {
        // Subtotal = 50.000. Admin memberi diskon 40.000 (80%).
        $payload = [
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'service_id' => $this->serviceItem->id,
                    'quantity' => 2,
                ],
            ],
            'discount_amount' => 40000,
        ];

        $response = $this->actingAs($this->admin)->post(route('orders.store'), $payload);
        $response->assertSessionHasNoErrors();
    }

    /**
     * Pelanggan nonaktif atau terarsip tidak dapat dipilih untuk pesanan baru.
     */
    public function test_inactive_customer_cannot_be_selected(): void
    {
        $inactiveCustomer = Customer::factory()->create([
            'is_active' => false,
        ]);

        $payload = [
            'customer_id' => $inactiveCustomer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'service_id' => $this->serviceKg->id,
                    'quantity' => 2,
                ],
            ],
        ];

        $response = $this->actingAs($this->kasir)->post(route('orders.store'), $payload);
        $response->assertSessionHasErrors(['customer_id']);
    }

    /**
     * Layanan nonaktif atau terarsip tidak dapat dipilih untuk pesanan baru.
     */
    public function test_inactive_service_cannot_be_selected(): void
    {
        $inactiveService = Service::factory()->create([
            'is_active' => false,
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'received_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'service_id' => $inactiveService->id,
                    'quantity' => 2,
                ],
            ],
        ];

        $response = $this->actingAs($this->kasir)->post(route('orders.store'), $payload);
        $response->assertSessionHasErrors(['items.0.service_id']);
    }

    /**
     * Endpoint tambah pelanggan cepat via AJAX mengembalikan respons JSON 201 dengan pelanggan baru.
     */
    public function test_quick_add_customer_ajax_endpoint_returns_json(): void
    {
        $payload = [
            'name' => 'Pelanggan Baru Kilat',
            'phone' => '089876543210',
            'address' => 'Jl. Mawar No. 45',
        ];

        $response = $this->actingAs($this->kasir)
            ->postJson(route('customers.store'), $payload);

        $response->assertCreated();
        $response->assertJson([
            'success' => true,
            'customer' => [
                'name' => 'Pelanggan Baru Kilat',
                'phone' => '089876543210',
            ],
        ]);

        $this->assertDatabaseHas('customers', [
            'name' => 'Pelanggan Baru Kilat',
            'phone' => '089876543210',
        ]);
    }

    /**
     * Estimasi waktu selesai tidak boleh mendahului waktu masuk pesanan.
     */
    public function test_estimated_completion_at_must_not_be_before_received_at(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'received_at' => '2026-10-13 14:00:00',
            'estimated_completion_at' => '2026-10-13 10:00:00', // Waktu mundur
            'items' => [
                [
                    'service_id' => $this->serviceKg->id,
                    'quantity' => 3,
                ],
            ],
        ];

        $response = $this->actingAs($this->kasir)->post(route('orders.store'), $payload);
        $response->assertSessionHasErrors(['estimated_completion_at']);
    }
}
