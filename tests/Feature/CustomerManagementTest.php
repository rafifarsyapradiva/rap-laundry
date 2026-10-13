<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
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
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
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

    public function test_guest_cannot_access_customers_module(): void
    {
        $this->get(route('customers.index'))->assertRedirect(route('login'));
        $this->get(route('customers.create'))->assertRedirect(route('login'));
        $this->post(route('customers.store'), [])->assertRedirect(route('login'));
    }

    public function test_authenticated_staff_can_view_customers_index_with_pagination(): void
    {
        Customer::factory()->count(15)->create();

        $response = $this->actingAs($this->kasir)->get(route('customers.index'));

        $response->assertOk();
        $response->assertViewIs('customers.index');
        $response->assertViewHas('customers');
        $response->assertSee('Data Pelanggan');
        $response->assertSee('Tambah Pelanggan Baru');
    }

    public function test_search_customers_by_name_phone_and_code(): void
    {
        $targetCustomer = Customer::factory()->create([
            'customer_code' => 'CUST-0001',
            'name' => 'Budi Santoso',
            'phone' => '081298765432',
            'is_active' => true,
        ]);

        $otherCustomer = Customer::factory()->create([
            'customer_code' => 'CUST-0002',
            'name' => 'Siti Rahma',
            'phone' => '081399998888',
            'is_active' => true,
        ]);

        // Cari berdasarkan nama
        $resName = $this->actingAs($this->kasir)->get(route('customers.index', ['q' => 'Budi']));
        $resName->assertSee('Budi Santoso');
        $resName->assertDontSee('Siti Rahma');

        // Cari berdasarkan nomor telepon
        $resPhone = $this->actingAs($this->kasir)->get(route('customers.index', ['q' => '081298765432']));
        $resPhone->assertSee('Budi Santoso');
        $resPhone->assertDontSee('Siti Rahma');

        // Cari berdasarkan kode
        $resCode = $this->actingAs($this->kasir)->get(route('customers.index', ['q' => 'CUST-0001']));
        $resCode->assertSee('Budi Santoso');
        $resCode->assertDontSee('Siti Rahma');
    }

    public function test_filter_customers_by_status(): void
    {
        $active = Customer::factory()->create(['name' => 'Pelanggan Aktif', 'is_active' => true]);
        $inactive = Customer::factory()->create(['name' => 'Pelanggan Nonaktif', 'is_active' => false]);
        $archived = Customer::factory()->create(['name' => 'Pelanggan Terarsip', 'is_active' => false]);
        $archived->delete(); // Soft delete

        // Filter aktif
        $resActive = $this->actingAs($this->kasir)->get(route('customers.index', ['status' => 'active']));
        $resActive->assertSee('Pelanggan Aktif');
        $resActive->assertDontSee('Pelanggan Nonaktif');
        $resActive->assertDontSee('Pelanggan Terarsip');

        // Filter nonaktif
        $resInactive = $this->actingAs($this->kasir)->get(route('customers.index', ['status' => 'inactive']));
        $resInactive->assertDontSee('Pelanggan Aktif');
        $resInactive->assertSee('Pelanggan Nonaktif');
        $resInactive->assertDontSee('Pelanggan Terarsip');

        // Filter terarsip
        $resArchived = $this->actingAs($this->kasir)->get(route('customers.index', ['status' => 'archived']));
        $resArchived->assertDontSee('Pelanggan Aktif');
        $resArchived->assertDontSee('Pelanggan Nonaktif');
        $resArchived->assertSee('Pelanggan Terarsip');
    }

    public function test_create_customer_with_automatic_code_and_phone_normalization(): void
    {
        $payload = [
            'name' => 'Dewi Lestari',
            'phone' => '+62 812-3456-7890',
            'address' => 'Jl. Kebon Jeruk No. 12',
            'notes' => 'Pakaian sutra butuh penanganan lembut.',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->kasir)->post(route('customers.store'), $payload);

        $customer = Customer::where('name', 'Dewi Lestari')->first();
        $this->assertNotNull($customer);
        $this->assertSame('CUST-0001', $customer->customer_code);
        $this->assertSame('081234567890', $customer->phone);
        $this->assertTrue($customer->is_active);

        $response->assertRedirect(route('customers.show', $customer));
        $response->assertSessionHas('success');

        // Jejak audit aktivitas terekam
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->kasir->id,
            'action' => ActivityAction::CustomerCreated->value,
            'subject_id' => $customer->id,
        ]);
    }

    public function test_create_customer_validates_required_fields_and_phone_format(): void
    {
        // 1. Validasi field wajib kosong
        $resEmpty = $this->actingAs($this->kasir)->post(route('customers.store'), [
            'name' => '',
            'phone' => '',
        ]);
        $resEmpty->assertSessionHasErrors(['name', 'phone']);

        // 2. Validasi format nomor telepon tidak valid
        $resInvalidPhone = $this->actingAs($this->kasir)->post(route('customers.store'), [
            'name' => 'Rina Sugiarto',
            'phone' => '123456', // terlalu pendek dan bukan format Indonesia
        ]);
        $resInvalidPhone->assertSessionHasErrors(['phone']);
    }

    public function test_update_customer_details(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Nama Lama',
            'phone' => '081211112222',
            'address' => 'Alamat Lama',
        ]);

        $payload = [
            'name' => 'Nama Baru',
            'phone' => '0812-9999-8888',
            'address' => 'Alamat Baru',
            'notes' => 'Catatan diperbarui',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->kasir)->put(route('customers.update', $customer), $payload);

        $customer->refresh();
        $this->assertSame('Nama Baru', $customer->name);
        $this->assertSame('081299998888', $customer->phone);
        $this->assertSame('Alamat Baru', $customer->address);

        $response->assertRedirect(route('customers.show', $customer));
        $response->assertSessionHas('success');

        // Audit log terekam
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->kasir->id,
            'action' => ActivityAction::CustomerUpdated->value,
            'subject_id' => $customer->id,
        ]);
    }

    public function test_archive_and_restore_customer(): void
    {
        $customer = Customer::factory()->create([
            'is_active' => true,
        ]);

        // Arsipkan (Soft Delete)
        $resArchive = $this->actingAs($this->kasir)->delete(route('customers.destroy', $customer));
        $resArchive->assertRedirect(route('customers.index'));

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
        $customer->refresh();
        $this->assertFalse($customer->is_active);

        // Pulihkan dari arsip (Restore)
        $resRestore = $this->actingAs($this->kasir)->post(route('customers.restore', $customer->id));
        $resRestore->assertRedirect(route('customers.show', $customer));

        $this->assertNotSoftDeleted('customers', ['id' => $customer->id]);
        $customer->refresh();
        $this->assertTrue($customer->is_active);
    }

    public function test_toggle_customer_active_status(): void
    {
        $customer = Customer::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->kasir)->patch(route('customers.toggle-status', $customer));
        $response->assertSessionHas('success');

        $customer->refresh();
        $this->assertFalse($customer->is_active);

        $this->actingAs($this->kasir)->patch(route('customers.toggle-status', $customer));
        $customer->refresh();
        $this->assertTrue($customer->is_active);
    }

    public function test_prevent_force_deletion_of_customer_with_orders(): void
    {
        $customer = Customer::factory()->create();

        // Buat order untuk pelanggan ini
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $this->kasir->id,
            'total_amount' => 50000,
            'laundry_status' => LaundryStatus::Diterima,
            'payment_status' => PaymentStatus::BelumDibayar,
        ]);

        // Coba force delete oleh Admin
        $response = $this->actingAs($this->admin)->delete(route('customers.force-delete', $customer->id));

        // Harus ditolak dan menampilkan error flash
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);

        // Order historis tetap utuh dan terhubung
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'customer_id' => $customer->id]);
    }

    public function test_allow_force_deletion_of_customer_without_orders_by_admin(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('customers.force-delete', $customer->id));

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_customer_detail_displays_metrics_and_order_history(): void
    {
        $customer = Customer::factory()->create([
            'customer_code' => 'CUST-0099',
            'name' => 'Pelanggan Setia',
            'phone' => '081288887777',
        ]);

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $this->kasir->id,
            'order_number' => 'LD-20261013-0099',
            'total_amount' => 75000,
            'laundry_status' => LaundryStatus::Dicuci,
            'payment_status' => PaymentStatus::DibayarSebagian,
            'received_at' => now(),
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'service_name_snapshot' => 'Cuci Kering',
            'billing_unit_snapshot' => BillingUnit::Kg,
            'quantity' => 3.5,
            'unit_price' => 10000,
            'subtotal' => 35000,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'recorded_by' => $this->kasir->id,
            'amount' => 25000,
            'method' => PaymentMethod::Tunai,
            'status' => PaymentRecordStatus::Valid,
        ]);

        $response = $this->actingAs($this->kasir)->get(route('customers.show', $customer->id));

        $response->assertOk();
        $response->assertViewIs('customers.show');
        $response->assertSee('CUST-0099');
        $response->assertSee('Pelanggan Setia');
        $response->assertSee('0812-8888-7777');
        $response->assertSee('LD-20261013-0099');
        $response->assertSee('Cuci Kering');
        $response->assertSee('Rp75.000');
        $response->assertSee('Rp50.000'); // Sisa tagihan belum lunas: 75.000 - 25.000 = 50.000
    }
}
