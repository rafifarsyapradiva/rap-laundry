<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Enums\BillingUnit;
use App\Enums\LaundryStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_model_casts_and_roles(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'admin_test']);
        $kasir = User::factory()->kasir()->create(['username' => 'kasir_test']);

        $this->assertInstanceOf(UserRole::class, $admin->role);
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isKasir());

        $this->assertInstanceOf(UserRole::class, $kasir->role);
        $this->assertTrue($kasir->isKasir());
        $this->assertFalse($kasir->isAdmin());
        $this->assertTrue($kasir->is_active);
    }

    public function test_customer_and_service_models_with_soft_deletes(): void
    {
        $customer = Customer::factory()->create();
        $service = Service::factory()->kg()->create(['price' => '8000.00']);

        $this->assertInstanceOf(BillingUnit::class, $service->billing_unit);
        $this->assertSame('8000.00', $service->price);

        // Uji soft delete
        $customer->delete();
        $service->delete();

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
        $this->assertSoftDeleted('services', ['id' => $service->id]);

        $this->assertNull(Customer::find($customer->id));
        $this->assertNotNull(Customer::withTrashed()->find($customer->id));

        $this->assertNull(Service::find($service->id));
        $this->assertNotNull(Service::withTrashed()->find($service->id));
    }

    public function test_order_model_relations_and_casts(): void
    {
        $user = User::factory()->kasir()->create();
        $customer = Customer::factory()->create();
        $service = Service::factory()->kg()->create(['price' => '8000.00']);

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $user->id,
            'subtotal' => '24000.00',
            'discount_amount' => '0.00',
            'extra_charge' => '0.00',
            'total_amount' => '24000.00',
            'laundry_status' => LaundryStatus::Diterima,
            'payment_status' => PaymentStatus::BelumDibayar,
        ]);

        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'service_id' => $service->id,
            'service_name_snapshot' => $service->name,
            'billing_unit_snapshot' => $service->billing_unit,
            'quantity' => '3.00',
            'unit_price' => '8000.00',
            'subtotal' => '24000.00',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'recorded_by' => $user->id,
            'amount' => '24000.00',
            'method' => PaymentMethod::Tunai,
            'status' => PaymentRecordStatus::Valid,
        ]);

        $history = OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => null,
            'to_status' => LaundryStatus::Diterima,
            'changed_by' => $user->id,
            'note' => 'Pesanan baru',
        ]);

        // Verifikasi relasi order
        $this->assertInstanceOf(Customer::class, $order->customer);
        $this->assertSame($customer->id, $order->customer->id);

        $this->assertInstanceOf(User::class, $order->creator);
        $this->assertSame($user->id, $order->creator->id);

        $this->assertCount(1, $order->items);
        $this->assertSame($item->id, $order->items->first()->id);

        $this->assertCount(1, $order->payments);
        $this->assertSame($payment->id, $order->payments->first()->id);

        $this->assertCount(1, $order->statusHistories);
        $this->assertSame($history->id, $order->statusHistories->first()->id);

        // Verifikasi cast order
        $this->assertInstanceOf(LaundryStatus::class, $order->laundry_status);
        $this->assertInstanceOf(PaymentStatus::class, $order->payment_status);
        $this->assertSame('24000.00', $order->total_amount);
        $this->assertSame('3.00', $item->quantity);
        $this->assertSame('8000.00', $item->unit_price);
        $this->assertSame('24000.00', $item->subtotal);
    }

    public function test_soft_deleted_customer_and_service_remain_accessible_from_historical_order(): void
    {
        $customer = Customer::factory()->create(['name' => 'Pelanggan Lama']);
        $service = Service::factory()->kg()->create(['name' => 'Layanan Lama', 'price' => '7000.00']);

        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'service_id' => $service->id,
            'service_name_snapshot' => 'Layanan Lama',
            'unit_price' => '7000.00',
            'subtotal' => '14000.00',
        ]);

        // Arsipkan (soft delete) pelanggan dan layanan master
        $customer->delete();
        $service->delete();

        // Relasi order->customer tetap berhasil memuat instance melalui withTrashed()
        $reloadedOrder = Order::with('customer', 'items.service')->find($order->id);
        $this->assertNotNull($reloadedOrder->customer);
        $this->assertSame('Pelanggan Lama', $reloadedOrder->customer->name);
        $this->assertTrue($reloadedOrder->customer->trashed());

        // Snapshot pada order item tetap utuh
        $reloadedItem = $reloadedOrder->items->first();
        $this->assertSame('Layanan Lama', $reloadedItem->service_name_snapshot);
        $this->assertSame('7000.00', $reloadedItem->unit_price);
        $this->assertNotNull($reloadedItem->service);
        $this->assertTrue($reloadedItem->service->trashed());
    }

    public function test_payment_void_and_scopes(): void
    {
        $order = Order::factory()->create();
        $validPayment = Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => '10000.00',
            'status' => PaymentRecordStatus::Valid,
        ]);
        $voidedPayment = Payment::factory()->voided()->create([
            'order_id' => $order->id,
            'amount' => '5000.00',
        ]);

        $this->assertTrue($validPayment->isValid());
        $this->assertFalse($validPayment->isVoided());
        $this->assertTrue($voidedPayment->isVoided());

        $this->assertCount(1, Payment::where('order_id', $order->id)->valid()->get());
        $this->assertCount(1, Payment::where('order_id', $order->id)->voided()->get());
        $this->assertCount(1, $order->validPayments);
    }

    public function test_business_setting_helpers(): void
    {
        BusinessSetting::set(BusinessSetting::KEY_BUSINESS_NAME, 'RAP Laundry Express');
        $this->assertSame('RAP Laundry Express', BusinessSetting::get(BusinessSetting::KEY_BUSINESS_NAME));

        $this->assertSame('default_val', BusinessSetting::get('unknown_key', 'default_val'));
    }

    public function test_activity_log_polymorphic_relation(): void
    {
        $user = User::factory()->admin()->create();
        $order = Order::factory()->create();

        $log = ActivityLog::create([
            'user_id' => $user->id,
            'action' => ActivityAction::OrderCreated,
            'subject_type' => Order::class,
            'subject_id' => $order->id,
            'description' => 'Pesanan berhasil dibuat',
            'metadata' => ['order_number' => $order->order_number],
        ]);

        $this->assertInstanceOf(ActivityAction::class, $log->action);
        $this->assertInstanceOf(Order::class, $log->subject);
        $this->assertSame($order->id, $log->subject->id);
        $this->assertSame($order->order_number, $log->metadata['order_number']);
    }

    public function test_database_seeder_executes_successfully(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Verifikasi akun seeder
        $this->assertDatabaseHas('users', ['username' => 'admin', 'role' => UserRole::Admin->value]);
        $this->assertDatabaseHas('users', ['username' => 'kasir', 'role' => UserRole::Kasir->value]);

        // Verifikasi master layanan
        $this->assertDatabaseHas('services', ['name' => 'Cuci Setrika']);
        $this->assertDatabaseHas('services', ['name' => 'Bed Cover Besar']);

        // Verifikasi pengaturan usaha
        $this->assertSame('RAP Laundry', BusinessSetting::get(BusinessSetting::KEY_BUSINESS_NAME));

        // Verifikasi transaksi demo
        $this->assertDatabaseHas('customers', ['customer_code' => 'CUST-0001']);
        $this->assertDatabaseCount('orders', 3);
        $this->assertDatabaseCount('order_items', 4);
        $this->assertDatabaseCount('payments', 2);
    }
}
