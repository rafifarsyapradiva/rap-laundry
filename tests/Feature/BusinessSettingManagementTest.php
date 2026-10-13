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
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessSettingManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;

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

        BusinessSetting::set(BusinessSetting::KEY_BUSINESS_NAME, 'RAP Laundry Asli');
        BusinessSetting::set(BusinessSetting::KEY_BUSINESS_ADDRESS, 'Jl. Asli No. 1');
        BusinessSetting::set(BusinessSetting::KEY_BUSINESS_PHONE, '081234567890');
        BusinessSetting::set(BusinessSetting::KEY_ORDER_NUMBER_PREFIX, 'RAP');
        BusinessSetting::set(BusinessSetting::KEY_DEFAULT_ESTIMATED_HOURS, '48');
        BusinessSetting::set(BusinessSetting::KEY_CASHIER_MAX_DISCOUNT_PERCENT, '20');
        BusinessSetting::set(BusinessSetting::KEY_PRINT_FORMAT, 'a4');
    }

    public function test_guest_is_redirected_to_login_when_accessing_settings(): void
    {
        $response = $this->get(route('admin.settings.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_cashier_is_forbidden_from_accessing_settings_and_gets_403(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('admin.settings.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_settings_page_with_current_values(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.settings.index'));

        $response->assertOk();
        $response->assertSee('RAP Laundry Asli');
        $response->assertSee('Jl. Asli No. 1');
        $response->assertSee('081234567890');
        $response->assertSee('RAP');
    }

    public function test_admin_can_update_business_settings_with_validation(): void
    {
        $payload = [
            'business_name' => 'Laundry Bintang Baru',
            'business_address' => 'Jl. Bintang Timur No. 88, Jakarta',
            'business_phone' => '089876543210',
            'operating_hours' => 'Senin - Minggu: 07.00 - 21.00 WIB',
            'order_number_prefix' => 'BB',
            'default_estimated_hours' => 24,
            'cashier_max_discount_percent' => 15,
            'receipt_footer_note' => 'Nota hilang harap bawa KTP asli saat pengambilan pakaian.',
            'print_format' => 'thermal',
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.settings.update'), $payload);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('success');

        $this->assertSame('Laundry Bintang Baru', BusinessSetting::get(BusinessSetting::KEY_BUSINESS_NAME));
        $this->assertSame('Jl. Bintang Timur No. 88, Jakarta', BusinessSetting::get(BusinessSetting::KEY_BUSINESS_ADDRESS));
        $this->assertSame('089876543210', BusinessSetting::get(BusinessSetting::KEY_BUSINESS_PHONE));
        $this->assertSame('BB', BusinessSetting::get(BusinessSetting::KEY_ORDER_NUMBER_PREFIX));
        $this->assertSame('24', BusinessSetting::get(BusinessSetting::KEY_DEFAULT_ESTIMATED_HOURS));
        $this->assertSame('15', BusinessSetting::get(BusinessSetting::KEY_CASHIER_MAX_DISCOUNT_PERCENT));
        $this->assertSame('thermal', BusinessSetting::get(BusinessSetting::KEY_PRINT_FORMAT));
        $this->assertSame('Nota hilang harap bawa KTP asli saat pengambilan pakaian.', BusinessSetting::get(BusinessSetting::KEY_RECEIPT_FOOTER_NOTE));

        // Verifikasi jejak audit aktivitas tercatat
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => ActivityAction::SettingUpdated->value,
            'subject_type' => BusinessSetting::class,
        ]);
    }

    public function test_invalid_business_settings_inputs_are_rejected(): void
    {
        // 1. Prefix kosong dan diskon melebihi 100%
        $response = $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'business_name' => '',
            'business_address' => '',
            'business_phone' => 'telepon-invalid',
            'order_number_prefix' => 'TOOLONGPREFIX',
            'default_estimated_hours' => 0,
            'cashier_max_discount_percent' => 150,
            'print_format' => 'invalid_format',
        ]);

        $response->assertSessionHasErrors([
            'business_name',
            'business_address',
            'business_phone',
            'order_number_prefix',
            'default_estimated_hours',
            'cashier_max_discount_percent',
            'print_format',
        ]);
    }

    public function test_admin_can_upload_valid_logo_and_remove_it(): void
    {
        Storage::fake('public');

        // Gunakan bytes PNG 1x1 nyata agar tidak bergantung pada ekstensi PHP GD di CLI Windows
        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAA=');
        $file = UploadedFile::fake()->createWithContent('logo.png', $pngBytes);

        $payload = [
            'business_name' => 'RAP Laundry Logo Test',
            'business_address' => 'Jl. Logo No. 5',
            'business_phone' => '081234567890',
            'order_number_prefix' => 'LG',
            'default_estimated_hours' => 48,
            'cashier_max_discount_percent' => 20,
            'print_format' => 'a4',
            'logo' => $file,
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.settings.update'), $payload);
        $response->assertRedirect(route('admin.settings.index'));

        $logoPath = BusinessSetting::get(BusinessSetting::KEY_BUSINESS_LOGO_PATH);
        $this->assertNotNull($logoPath);
        Storage::disk('public')->assertExists($logoPath);

        // Hapus logo dengan opsi remove_logo
        $removePayload = array_merge($payload, [
            'logo' => null,
            'remove_logo' => 1,
        ]);

        $responseRemove = $this->actingAs($this->admin)->put(route('admin.settings.update'), $removePayload);
        $responseRemove->assertRedirect(route('admin.settings.index'));

        $this->assertNull(BusinessSetting::get(BusinessSetting::KEY_BUSINESS_LOGO_PATH));
        Storage::disk('public')->assertMissing($logoPath);
    }

    public function test_oversized_and_invalid_logo_files_are_rejected(): void
    {
        Storage::fake('public');

        // File lebih dari 1MB (1025 KB)
        $oversizedFile = UploadedFile::fake()->create('huge.png', 1050, 'image/png');

        $response = $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'business_name' => 'RAP Laundry',
            'business_address' => 'Jl. Alamat No. 1',
            'business_phone' => '081234567890',
            'order_number_prefix' => 'RP',
            'default_estimated_hours' => 48,
            'cashier_max_discount_percent' => 20,
            'print_format' => 'a4',
            'logo' => $oversizedFile,
        ]);

        $response->assertSessionHasErrors(['logo']);
    }

    public function test_updated_settings_are_applied_to_order_receipt(): void
    {
        // Ubah identitas usaha
        BusinessSetting::set(BusinessSetting::KEY_BUSINESS_NAME, 'Laundry Sentosa Abadi');
        BusinessSetting::set(BusinessSetting::KEY_BUSINESS_ADDRESS, 'Jl. Sentosa Raya No. 99');
        BusinessSetting::set(BusinessSetting::KEY_BUSINESS_PHONE, '081122334455');
        BusinessSetting::set(BusinessSetting::KEY_RECEIPT_FOOTER_NOTE, 'Syarat Khusus: Pakaian putih dicuci terpisah.');

        $customer = Customer::factory()->create();
        $service = Service::factory()->create([
            'billing_unit' => BillingUnit::Kg,
            'price' => 10000,
        ]);

        $order = Order::create([
            'order_number' => 'LD-20261013-0001',
            'customer_id' => $customer->id,
            'created_by' => $this->cashier->id,
            'received_at' => now(),
            'subtotal' => 20000,
            'discount_amount' => 0,
            'extra_charge' => 0,
            'total_amount' => 20000,
            'laundry_status' => LaundryStatus::Diterima,
            'payment_status' => PaymentStatus::BelumDibayar,
        ]);

        $order->items()->create([
            'service_id' => $service->id,
            'service_name_snapshot' => $service->name,
            'billing_unit_snapshot' => BillingUnit::Kg,
            'quantity' => 2.0,
            'unit_price' => 10000,
            'subtotal' => 20000,
        ]);

        $response = $this->actingAs($this->cashier)->get(route('orders.print', $order));

        $response->assertOk();
        $response->assertSee('Laundry Sentosa Abadi');
        $response->assertSee('Jl. Sentosa Raya No. 99');
        $response->assertSee('081122334455');
        $response->assertSee('Syarat Khusus: Pakaian putih dicuci terpisah.');
    }
}
