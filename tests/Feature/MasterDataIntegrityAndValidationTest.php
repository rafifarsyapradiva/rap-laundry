<?php

namespace Tests\Feature;

use App\Enums\BillingUnit;
use App\Enums\LaundryStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataIntegrityAndValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $kasir;

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
    }

    // =========================================================================
    // GRUP 1: UJI VALIDASI NAMA, SATUAN, HARGA, DAN STATUS
    // =========================================================================

    public function test_customer_boundary_validation_for_name_phone_and_status(): void
    {
        // 1. Nama kosong, spasi saja, dan terlalu pendek (< 3)
        $this->actingAs($this->kasir)->post(route('customers.store'), [
            'name' => '  ',
            'phone' => '08123456789',
        ])->assertSessionHasErrors(['name']);

        $this->actingAs($this->kasir)->post(route('customers.store'), [
            'name' => 'Al',
            'phone' => '08123456789',
        ])->assertSessionHasErrors(['name']);

        // 2. Nama terlalu panjang (> 100 karakter)
        $this->actingAs($this->kasir)->post(route('customers.store'), [
            'name' => str_repeat('A', 101),
            'phone' => '08123456789',
        ])->assertSessionHasErrors(['name']);

        // 3. Format nomor telepon cacat / bukan nomor Indonesia
        $this->actingAs($this->kasir)->post(route('customers.store'), [
            'name' => 'Pelanggan Valid',
            'phone' => 'abcdefghij',
        ])->assertSessionHasErrors(['phone']);

        $this->actingAs($this->kasir)->post(route('customers.store'), [
            'name' => 'Pelanggan Valid',
            'phone' => '1234567', // Terlalu pendek
        ])->assertSessionHasErrors(['phone']);

        // 4. Input valid dengan format nomor telepon bervariasi (+62, strip, spasi)
        $response = $this->actingAs($this->kasir)->post(route('customers.store'), [
            'name' => 'Hendra Setiawan',
            'phone' => '+62 813-9876-5432',
            'is_active' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customers', [
            'name' => 'Hendra Setiawan',
            'phone' => '081398765432',
            'is_active' => true,
        ]);
    }

    public function test_service_boundary_validation_for_name_billing_unit_price_and_estimated_hours(): void
    {
        // 1. Nama layanan kosong dan terlalu pendek
        $this->actingAs($this->admin)->post(route('services.store'), [
            'name' => 'Cu',
            'billing_unit' => 'kg',
            'price' => '6000',
        ])->assertSessionHasErrors(['name']);

        // 2. Satuan penagihan tidak valid (bukan enum kg, item, package)
        $this->actingAs($this->admin)->post(route('services.store'), [
            'name' => 'Cuci Karpet',
            'billing_unit' => 'meter_persegi',
            'price' => '15000',
        ])->assertSessionHasErrors(['billing_unit']);

        // 3. Harga tarif negatif
        $this->actingAs($this->admin)->post(route('services.store'), [
            'name' => 'Cuci Karpet',
            'billing_unit' => 'item',
            'price' => '-25000',
        ])->assertSessionHasErrors(['price']);

        // 4. Estimasi jam di luar batas (0 jam atau > 720 jam)
        $this->actingAs($this->admin)->post(route('services.store'), [
            'name' => 'Cuci Kilat',
            'billing_unit' => 'package',
            'price' => '30000',
            'estimated_hours' => 0,
        ])->assertSessionHasErrors(['estimated_hours']);

        $this->actingAs($this->admin)->post(route('services.store'), [
            'name' => 'Cuci Kilat',
            'billing_unit' => 'package',
            'price' => '30000',
            'estimated_hours' => 800,
        ])->assertSessionHasErrors(['estimated_hours']);

        // 5. Duplikasi nama layanan
        Service::factory()->create(['name' => 'Cuci Kering Standar']);

        $this->actingAs($this->admin)->post(route('services.store'), [
            'name' => 'Cuci Kering Standar',
            'billing_unit' => 'kg',
            'price' => '7000',
        ])->assertSessionHasErrors(['name']);
    }

    public function test_service_accepts_zero_price_and_indonesian_decimal_format(): void
    {
        // Harga Rp0 diizinkan (misal layanan gratis atau voucher promo)
        $resZero = $this->actingAs($this->admin)->post(route('services.store'), [
            'name' => 'Parfum Pakaian Tambahan',
            'billing_unit' => 'item',
            'price' => '0',
            'is_active' => '1',
        ]);
        $resZero->assertSessionHasNoErrors();
        $this->assertDatabaseHas('services', [
            'name' => 'Parfum Pakaian Tambahan',
            'price' => '0.00',
        ]);

        // Format desimal koma Indonesia (Rp 12.500,50)
        $resDecimal = $this->actingAs($this->admin)->post(route('services.store'), [
            'name' => 'Cuci Gaun Pengantin',
            'billing_unit' => 'item',
            'price' => '12.500,50',
            'is_active' => '1',
        ]);
        $resDecimal->assertSessionHasNoErrors();
        $this->assertDatabaseHas('services', [
            'name' => 'Cuci Gaun Pengantin',
            'price' => '12500.50',
        ]);
    }

    // =========================================================================
    // GRUP 2: UJI PENCARIAN DAN PAGINATION
    // =========================================================================

    public function test_customer_search_handles_phone_formatting_variations_and_code(): void
    {
        $target = Customer::factory()->create([
            'customer_code' => 'CUST-0077',
            'name' => 'Rudy Hartono',
            'phone' => '081299998888',
        ]);

        Customer::factory()->create([
            'customer_code' => 'CUST-0078',
            'name' => 'Alan Budikusuma',
            'phone' => '081377776666',
        ]);

        // Cari dengan input berformat strip: 0812-9999-8888
        $resPhone = $this->actingAs($this->kasir)->get(route('customers.index', ['q' => '0812-9999-8888']));
        $resPhone->assertSee('Rudy Hartono');
        $resPhone->assertDontSee('Alan Budikusuma');

        // Cari dengan kode pelanggan
        $resCode = $this->actingAs($this->kasir)->get(route('customers.index', ['q' => 'CUST-0077']));
        $resCode->assertSee('Rudy Hartono');
        $resCode->assertDontSee('Alan Budikusuma');
    }

    public function test_service_search_and_filter_by_billing_unit_and_status(): void
    {
        $kgActive = Service::factory()->create([
            'name' => 'Cuci Kering Kiloan',
            'description' => 'Cuci kiloan murah wangi',
            'billing_unit' => BillingUnit::Kg,
            'is_active' => true,
        ]);

        $itemActive = Service::factory()->create([
            'name' => 'Cuci Kering Jas',
            'description' => 'Jas per satuan helai',
            'billing_unit' => BillingUnit::Item,
            'is_active' => true,
        ]);

        $kgInactive = Service::factory()->create([
            'name' => 'Cuci Kering Express',
            'billing_unit' => BillingUnit::Kg,
            'is_active' => false,
        ]);

        // Cari teks "Cuci Kering" difilter unit = kg dan status = active
        $response = $this->actingAs($this->kasir)->get(route('services.index', [
            'q' => 'Cuci Kering',
            'unit' => 'kg',
            'status' => 'active',
        ]));

        $response->assertSee('Cuci Kering Kiloan');
        $response->assertDontSee('Cuci Kering Jas'); // unit beda
        $response->assertDontSee('Cuci Kering Express'); // status nonaktif
    }

    public function test_pagination_preserves_query_string_parameters_across_pages(): void
    {
        Customer::factory()->count(18)->create(['name' => 'Pelanggan Uji']);

        $response = $this->actingAs($this->kasir)->get(route('customers.index', [
            'q' => 'Pelanggan Uji',
            'status' => 'active',
            'page' => 2,
        ]));

        $response->assertOk();
        // Memastikan link pagination menyertakan query string pencarian
        $response->assertSee('q=Pelanggan%20Uji');
        $response->assertSee('status=active');
    }

    public function test_out_of_bounds_pagination_page_renders_empty_state_gracefully(): void
    {
        Customer::factory()->count(3)->create();

        // Mengakses halaman 999 yang tidak memiliki data
        $response = $this->actingAs($this->kasir)->get(route('customers.index', ['page' => 999]));

        $response->assertOk();
        $response->assertSee('Tidak Ada Data Pelanggan Ditemukan');
    }

    // =========================================================================
    // GRUP 3: UJI MATRIKS OTORISASI RBAC
    // =========================================================================

    public function test_unauthenticated_guests_are_redirected_to_login_for_all_master_data_routes(): void
    {
        $customer = Customer::factory()->create();
        $service = Service::factory()->create();

        $this->get(route('customers.index'))->assertRedirect(route('login'));
        $this->get(route('customers.show', $customer->id))->assertRedirect(route('login'));
        $this->get(route('services.index'))->assertRedirect(route('login'));
        $this->get(route('services.show', $service->id))->assertRedirect(route('login'));
    }

    public function test_kasir_role_authorization_matrix_for_customer_and_service(): void
    {
        $customer = Customer::factory()->create();
        $service = Service::factory()->create();

        // 1. Kasir boleh mengelola pelanggan (karena melayani pelanggan harian)
        $this->actingAs($this->kasir)->get(route('customers.create'))->assertOk();
        $this->actingAs($this->kasir)->get(route('customers.edit', $customer->id))->assertOk();
        $this->actingAs($this->kasir)->patch(route('customers.toggle-status', $customer->id))->assertRedirect();

        // 2. Kasir DILARANG keras memutasi master layanan (403 Forbidden)
        $this->actingAs($this->kasir)->get(route('services.create'))->assertForbidden();
        $this->actingAs($this->kasir)->post(route('services.store'), [])->assertForbidden();
        $this->actingAs($this->kasir)->get(route('services.edit', $service->id))->assertForbidden();
        $this->actingAs($this->kasir)->put(route('services.update', $service->id), [])->assertForbidden();
        $this->actingAs($this->kasir)->delete(route('services.destroy', $service->id))->assertForbidden();
        $this->actingAs($this->kasir)->patch(route('services.toggle-status', $service->id))->assertForbidden();
        $this->actingAs($this->kasir)->delete(route('services.force-delete', $service->id))->assertForbidden();
    }

    // =========================================================================
    // GRUP 4: UJI INTEGRITAS ARSIP DATA BERTRANSAKSI & SNAPSHOT
    // =========================================================================

    public function test_archived_customer_with_orders_maintains_relationship_via_with_trashed(): void
    {
        $customer = Customer::factory()->create(['name' => 'Pelanggan Bersejarah']);

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $this->kasir->id,
            'order_number' => 'LD-20261013-0001',
            'total_amount' => 50000,
        ]);

        // Kasir mengarsipkan pelanggan
        $this->actingAs($this->kasir)->delete(route('customers.destroy', $customer->id));

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);

        // Relasi order historis tetap dapat mengakses nama pelanggan via withTrashed
        $order->refresh();
        $this->assertNotNull($order->customer);
        $this->assertSame('Pelanggan Bersejarah', $order->customer->name);
    }

    public function test_archived_service_with_order_items_maintains_snapshot_and_relationship(): void
    {
        $service = Service::factory()->create([
            'name' => 'Cuci Boneka Besar',
            'billing_unit' => BillingUnit::Item,
            'price' => '35000.00',
        ]);

        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id, 'created_by' => $this->kasir->id]);

        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'service_id' => $service->id,
            'service_name_snapshot' => 'Cuci Boneka Besar',
            'billing_unit_snapshot' => BillingUnit::Item,
            'quantity' => 2,
            'unit_price' => '35000.00',
            'subtotal' => '70000.00',
        ]);

        // Admin mengarsipkan layanan
        $this->actingAs($this->admin)->delete(route('services.destroy', $service->id));

        $this->assertSoftDeleted('services', ['id' => $service->id]);

        // Relasi order item ke service tetap ada via withTrashed
        $orderItem->refresh();
        $this->assertNotNull($orderItem->service);
        $this->assertSame('Cuci Boneka Besar', $orderItem->service->name);

        // Snapshot nama dan tarif pada nota lama tetap utuh
        $this->assertSame('Cuci Boneka Besar', $orderItem->service_name_snapshot);
        $this->assertSame('35000.00', (string) $orderItem->unit_price);
        $this->assertSame('70000.00', (string) $orderItem->subtotal);
    }

    public function test_service_price_mutation_does_not_mutate_existing_order_item_snapshot(): void
    {
        $service = Service::factory()->create([
            'name' => 'Cuci Bed Cover King',
            'billing_unit' => BillingUnit::Item,
            'price' => '30000.00',
        ]);

        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id, 'created_by' => $this->kasir->id]);

        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'service_id' => $service->id,
            'service_name_snapshot' => 'Cuci Bed Cover King',
            'billing_unit_snapshot' => BillingUnit::Item,
            'quantity' => 1,
            'unit_price' => '30000.00',
            'subtotal' => '30000.00',
        ]);

        // Tarif master diubah drastis dari 30.000 ke 55.000
        $this->actingAs($this->admin)->put(route('services.update', $service->id), [
            'name' => 'Cuci Bed Cover King (Harga Baru)',
            'billing_unit' => 'item',
            'price' => '55000.00',
            'is_active' => '1',
        ]);

        $service->refresh();
        $this->assertSame('55000.00', (string) $service->price);

        // Nilai transaksi lama TIDAK BERUBAH
        $item->refresh();
        $this->assertSame('30000.00', (string) $item->unit_price);
        $this->assertSame('30000.00', (string) $item->subtotal);
        $this->assertSame('Cuci Bed Cover King', $item->service_name_snapshot);
    }

    public function test_inactive_and_archived_services_are_strictly_excluded_from_active_scope(): void
    {
        $active = Service::factory()->create(['name' => 'Layanan Siap Digunakan', 'is_active' => true]);
        $inactive = Service::factory()->create(['name' => 'Layanan Istirahat', 'is_active' => false]);
        $archived = Service::factory()->create(['name' => 'Layanan Dihapus', 'is_active' => true]);
        $archived->delete();

        $available = Service::active()->pluck('id');

        $this->assertTrue($available->contains($active->id));
        $this->assertFalse($available->contains($inactive->id));
        $this->assertFalse($available->contains($archived->id));
    }

    public function test_force_delete_is_strictly_rejected_for_customers_and_services_with_transaction_history(): void
    {
        // 1. Pelanggan bertransaksi
        $customer = Customer::factory()->create();
        Order::factory()->create(['customer_id' => $customer->id, 'created_by' => $this->kasir->id]);

        $resCustomer = $this->actingAs($this->admin)->delete(route('customers.force-delete', $customer->id));
        $resCustomer->assertSessionHas('error');
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);

        // 2. Layanan bertransaksi
        $service = Service::factory()->create();
        $dummyCustomer = Customer::factory()->create();
        $dummyOrder = Order::factory()->create(['customer_id' => $dummyCustomer->id, 'created_by' => $this->kasir->id]);

        OrderItem::factory()->create([
            'order_id' => $dummyOrder->id,
            'service_id' => $service->id,
            'service_name_snapshot' => $service->name,
            'billing_unit_snapshot' => $service->billing_unit,
        ]);

        $resService = $this->actingAs($this->admin)->delete(route('services.force-delete', $service->id));
        $resService->assertSessionHas('error');
        $this->assertDatabaseHas('services', ['id' => $service->id]);
    }
}
