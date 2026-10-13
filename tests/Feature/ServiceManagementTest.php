<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
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

class ServiceManagementTest extends TestCase
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

    public function test_guest_cannot_access_services_module(): void
    {
        $this->get(route('services.index'))->assertRedirect(route('login'));
        $this->get(route('services.create'))->assertRedirect(route('login'));
        $this->post(route('services.store'), [])->assertRedirect(route('login'));
    }

    public function test_kasir_can_view_services_index_and_detail_but_forbidden_from_mutation(): void
    {
        $service = Service::factory()->create([
            'name' => 'Cuci Kering Standar',
            'billing_unit' => BillingUnit::Kg,
            'price' => 6000,
        ]);

        // Kasir diizinkan melihat katalog dan detail
        $this->actingAs($this->kasir)->get(route('services.index'))->assertOk();
        $this->actingAs($this->kasir)->get(route('services.show', $service->id))->assertOk();

        // Kasir DILARANG mengakses form tambah atau mutasi
        $this->actingAs($this->kasir)->get(route('services.create'))->assertForbidden();
        $this->actingAs($this->kasir)->post(route('services.store'), [
            'name' => 'Layanan Ilegal',
            'billing_unit' => 'kg',
            'price' => 10000,
        ])->assertForbidden();

        $this->actingAs($this->kasir)->get(route('services.edit', $service->id))->assertForbidden();
        $this->actingAs($this->kasir)->put(route('services.update', $service->id), [
            'name' => 'Layanan Edit Ilegal',
            'billing_unit' => 'kg',
            'price' => 15000,
        ])->assertForbidden();

        $this->actingAs($this->kasir)->delete(route('services.destroy', $service->id))->assertForbidden();
        $this->actingAs($this->kasir)->patch(route('services.toggle-status', $service->id))->assertForbidden();
        $this->actingAs($this->kasir)->delete(route('services.force-delete', $service->id))->assertForbidden();
    }

    public function test_admin_can_view_services_with_pagination_and_filter_by_unit_and_status(): void
    {
        $kgService = Service::factory()->create(['name' => 'Layanan Kiloan', 'billing_unit' => BillingUnit::Kg, 'is_active' => true]);
        $itemService = Service::factory()->create(['name' => 'Layanan Satuan Item', 'billing_unit' => BillingUnit::Item, 'is_active' => true]);
        $packageService = Service::factory()->create(['name' => 'Layanan Paket Kilat', 'billing_unit' => BillingUnit::Paket, 'is_active' => false]);
        $archivedService = Service::factory()->create(['name' => 'Layanan Diarsipkan', 'billing_unit' => BillingUnit::Kg, 'is_active' => false]);
        $archivedService->delete();

        // Filter unit = kg
        $resKg = $this->actingAs($this->admin)->get(route('services.index', ['unit' => 'kg']));
        $resKg->assertSee('Layanan Kiloan');
        $resKg->assertDontSee('Layanan Satuan Item');

        // Filter unit = item
        $resItem = $this->actingAs($this->admin)->get(route('services.index', ['unit' => 'item']));
        $resItem->assertDontSee('Layanan Kiloan');
        $resItem->assertSee('Layanan Satuan Item');

        // Filter status = inactive
        $resInactive = $this->actingAs($this->admin)->get(route('services.index', ['status' => 'inactive']));
        $resInactive->assertSee('Layanan Paket Kilat');
        $resInactive->assertDontSee('Layanan Kiloan');

        // Filter status = archived
        $resArchived = $this->actingAs($this->admin)->get(route('services.index', ['status' => 'archived']));
        $resArchived->assertSee('Layanan Diarsipkan');
        $resArchived->assertDontSee('Layanan Kiloan');
    }

    public function test_admin_can_create_new_service_with_validation(): void
    {
        $payload = [
            'name' => 'Cuci Karpet Tebal',
            'description' => 'Pencucian karpet dengan mesin pengering khusus.',
            'billing_unit' => 'item',
            'price' => '45000',
            'estimated_hours' => '48',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->admin)->post(route('services.store'), $payload);

        $service = Service::where('name', 'Cuci Karpet Tebal')->first();
        $this->assertNotNull($service);
        $this->assertSame('45000.00', (string) $service->price);
        $this->assertSame(BillingUnit::Item, $service->billing_unit);
        $this->assertSame(48, $service->estimated_hours);

        $response->assertRedirect(route('services.show', $service));
        $response->assertSessionHas('success');

        // Jejak audit terekam
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => ActivityAction::ServiceCreated->value,
            'subject_id' => $service->id,
        ]);

        // Uji validasi harga negatif dan field kosong
        $resNegative = $this->actingAs($this->admin)->post(route('services.store'), [
            'name' => 'Layanan Harga Negatif',
            'billing_unit' => 'kg',
            'price' => '-5000',
        ]);
        $resNegative->assertSessionHasErrors(['price']);

        $resEmpty = $this->actingAs($this->admin)->post(route('services.store'), [
            'name' => '',
            'billing_unit' => '',
            'price' => '',
        ]);
        $resEmpty->assertSessionHasErrors(['name', 'billing_unit', 'price']);
    }

    public function test_admin_can_update_service_and_price_change_is_audited(): void
    {
        $service = Service::factory()->create([
            'name' => 'Cuci Setrika Reguler',
            'billing_unit' => BillingUnit::Kg,
            'price' => '8000.00',
            'estimated_hours' => 24,
        ]);

        $payload = [
            'name' => 'Cuci Setrika Reguler (Wangi)',
            'description' => 'Menggunakan pewangi premium.',
            'billing_unit' => 'kg',
            'price' => '10000.00', // Harga naik dari 8000 ke 10000
            'estimated_hours' => '36',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->admin)->put(route('services.update', $service->id), $payload);

        $service->refresh();
        $this->assertSame('Cuci Setrika Reguler (Wangi)', $service->name);
        $this->assertSame('10000.00', (string) $service->price);
        $this->assertSame(36, $service->estimated_hours);

        $response->assertRedirect(route('services.show', $service));
        $response->assertSessionHas('success');

        // Jejak audit perubahan harga terekam secara khusus
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => ActivityAction::ServicePriceChanged->value,
            'subject_id' => $service->id,
        ]);
    }

    public function test_critical_financial_integrity_price_change_does_not_mutate_existing_order_snapshots(): void
    {
        // 1. Buat layanan master awal dengan harga Rp8.000
        $service = Service::factory()->create([
            'name' => 'Cuci Kering 1 Hari',
            'billing_unit' => BillingUnit::Kg,
            'price' => '8000.00',
        ]);

        $customer = Customer::factory()->create();

        // 2. Buat pesanan dengan snapshot harga Rp8.000 x 3.5 kg = Rp28.000
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $this->kasir->id,
            'subtotal' => 28000,
            'total_amount' => 28000,
            'laundry_status' => LaundryStatus::Diterima,
            'payment_status' => PaymentStatus::BelumDibayar,
        ]);

        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'service_id' => $service->id,
            'service_name_snapshot' => 'Cuci Kering 1 Hari',
            'billing_unit_snapshot' => BillingUnit::Kg,
            'quantity' => 3.5,
            'unit_price' => '8000.00',
            'subtotal' => '28000.00',
        ]);

        // 3. Admin mengubah harga master layanan dari Rp8.000 menjadi Rp15.000 dan nama layanan
        $this->actingAs($this->admin)->put(route('services.update', $service->id), [
            'name' => 'Cuci Kering 1 Hari (Tarif Baru)',
            'billing_unit' => 'kg',
            'price' => '15000.00',
            'is_active' => '1',
        ]);

        // 4. Verifikasi bahwa harga master di database sudah berubah ke Rp15.000
        $service->refresh();
        $this->assertSame('15000.00', (string) $service->price);

        // 5. VERIFIKASI KRUSIAL INTEGRITAS FINANSIAL:
        // Item pesanan lama HARUS TETAP Rp8.000.00 dan Subtotal Rp28.000.00!
        $orderItem->refresh();
        $this->assertSame('8000.00', (string) $orderItem->unit_price, 'Harga snapshot lama tidak boleh berubah!');
        $this->assertSame('28000.00', (string) $orderItem->subtotal, 'Subtotal nota lama tidak boleh berubah!');
        $this->assertSame('Cuci Kering 1 Hari', $orderItem->service_name_snapshot, 'Nama snapshot lama tidak boleh berubah!');
    }

    public function test_inactive_and_archived_services_are_excluded_from_active_scope(): void
    {
        $activeService = Service::factory()->create(['name' => 'Aktif', 'is_active' => true]);
        $inactiveService = Service::factory()->create(['name' => 'Nonaktif', 'is_active' => false]);
        $archivedService = Service::factory()->create(['name' => 'Terarsip', 'is_active' => true]);
        $archivedService->delete(); // Soft delete

        $activeServices = Service::active()->get();

        $this->assertTrue($activeServices->contains('id', $activeService->id));
        $this->assertFalse($activeServices->contains('id', $inactiveService->id), 'Layanan nonaktif tidak boleh masuk dalam active scope.');
        $this->assertFalse($activeServices->contains('id', $archivedService->id), 'Layanan terarsip tidak boleh masuk dalam active scope.');
    }

    public function test_archive_and_restore_service_by_admin(): void
    {
        $service = Service::factory()->create(['is_active' => true]);

        // Arsipkan
        $resArchive = $this->actingAs($this->admin)->delete(route('services.destroy', $service->id));
        $resArchive->assertRedirect(route('services.index'));

        $this->assertSoftDeleted('services', ['id' => $service->id]);
        $service->refresh();
        $this->assertFalse($service->is_active);

        // Pulihkan
        $resRestore = $this->actingAs($this->admin)->post(route('services.restore', $service->id));
        $resRestore->assertRedirect(route('services.show', $service->id));

        $this->assertNotSoftDeleted('services', ['id' => $service->id]);
        $service->refresh();
        $this->assertTrue($service->is_active);
    }

    public function test_toggle_service_active_status(): void
    {
        $service = Service::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin)->patch(route('services.toggle-status', $service->id));
        $service->refresh();
        $this->assertFalse($service->is_active);

        $this->actingAs($this->admin)->patch(route('services.toggle-status', $service->id));
        $service->refresh();
        $this->assertTrue($service->is_active);
    }

    public function test_prevent_force_deletion_of_service_with_existing_orders(): void
    {
        $service = Service::factory()->create();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id, 'created_by' => $this->kasir->id]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'service_id' => $service->id,
            'service_name_snapshot' => $service->name,
            'billing_unit_snapshot' => $service->billing_unit,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('services.force-delete', $service->id));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('services', ['id' => $service->id]);
    }

    public function test_allow_force_deletion_of_service_without_orders(): void
    {
        $service = Service::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('services.force-delete', $service->id));

        $response->assertRedirect(route('services.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }
}
