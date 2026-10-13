<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Enums\BillingUnit;
use App\Enums\LaundryStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Validasi server-side di semua form: Input cacat wajib ditolak sedini mungkin.
     */
    public function test_server_side_validation_rejects_malformed_inputs_on_all_core_forms(): void
    {
        $admin = User::factory()->admin()->create();
        $cashier = User::factory()->kasir()->create();

        // A. Form Pelanggan: Nama kosong, nomor telepon cacat
        $responseCustomer = $this->actingAs($cashier)->post(route('customers.store'), [
            'name' => '',
            'phone' => 'abc-bukan-angka',
        ]);
        $responseCustomer->assertSessionHasErrors(['name', 'phone']);

        // B. Form Master Layanan: Harga negatif, nama kurang dari 3 karakter
        $responseService = $this->actingAs($admin)->post(route('services.store'), [
            'name' => 'ab',
            'billing_unit' => 'kg',
            'price' => -5000,
        ]);
        $responseService->assertSessionHasErrors(['name', 'price']);

        // C. Form Pesanan Baru: Item kosong, pelanggan tidak valid
        $responseOrder = $this->actingAs($cashier)->post(route('orders.store'), [
            'customer_id' => 999999,
            'items' => [],
        ]);
        $responseOrder->assertSessionHasErrors(['customer_id', 'items']);

        // D. Form Pembayaran: Nominal 0 atau negatif
        $customer = Customer::factory()->create();
        $service = Service::factory()->create(['price' => 20000]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'total_amount' => 20000,
            'payment_status' => PaymentStatus::BelumDibayar,
        ]);

        $responsePayment = $this->actingAs($cashier)->post(route('orders.payments.store', $order), [
            'amount' => 0,
            'payment_method' => PaymentMethod::Tunai->value,
            'paid_at' => now()->format('Y-m-d H:i:s'),
        ]);
        $responsePayment->assertSessionHasErrors('amount');

        // E. Form Void Pembayaran: Alasan kurang dari 10 karakter
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 10000,
            'recorded_by' => $cashier->id,
        ]);

        $responseVoid = $this->actingAs($admin)->patch(route('payments.void', $payment), [
            'reason' => 'pendek',
        ]);
        $responseVoid->assertSessionHasErrors('reason');

        // F. Form Pengaturan: Format prefix tidak valid (terlalu pendek / simbol aneh)
        $responseSetting = $this->actingAs($admin)->put(route('admin.settings.update'), [
            'business_name' => 'Laundry Tes',
            'business_address' => 'Jl. Uji No. 1',
            'business_phone' => '081234567890',
            'order_number_prefix' => '#',
            'default_estimated_hours' => 24,
            'cashier_max_discount_percent' => 10,
            'print_format' => 'a4',
        ]);
        $responseSetting->assertSessionHasErrors('order_number_prefix');

        // G. Form Pengguna: Password kurang dari 8 karakter
        $responseUser = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Staf Baru',
            'username' => 'stafbaru',
            'email' => 'staf@laundry.id',
            'password' => '12345',
            'password_confirmation' => '12345',
            'role' => UserRole::Kasir->value,
        ]);
        $responseUser->assertSessionHasErrors('password');
    }

    /**
     * 2. Proteksi CSRF: Semua aksi mutasi wajib memverifikasi token sesi.
     */
    public function test_csrf_token_presence_in_forms_and_state_change_security(): void
    {
        $cashier = User::factory()->kasir()->create();

        // Pastikan form-form mutasi menyertakan directive @csrf (_token)
        $this->actingAs($cashier)->get(route('orders.create'))->assertSee('name="_token"', false);
        $this->actingAs($cashier)->get(route('customers.create'))->assertSee('name="_token"', false);

        // Operasi perubahan state dilarang dipanggil via GET
        $this->actingAs($cashier)->get('/logout')->assertStatus(405);
    }

    /**
     * 3. Otorisasi server-side: Rute sensitif mutlak menolak peran Kasir dengan respon 403.
     */
    public function test_sensitive_routes_strictly_forbidden_for_cashier_role(): void
    {
        $cashier = User::factory()->kasir()->create();

        // Modul Admin
        $this->actingAs($cashier)->get(route('admin.reports.index'))->assertStatus(403);
        $this->actingAs($cashier)->get(route('admin.reports.orders.export'))->assertStatus(403);
        $this->actingAs($cashier)->get(route('admin.reports.payments.export'))->assertStatus(403);
        $this->actingAs($cashier)->get(route('admin.settings.index'))->assertStatus(403);
        $this->actingAs($cashier)->get(route('admin.audit-logs.index'))->assertStatus(403);
        $this->actingAs($cashier)->get(route('admin.users.index'))->assertStatus(403);
        $this->actingAs($cashier)->get(route('admin.users.create'))->assertStatus(403);

        // Tambah/Edit Master Layanan
        $this->actingAs($cashier)->get(route('services.create'))->assertStatus(403);

        // Void Pembayaran
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $payment = Payment::factory()->create(['order_id' => $order->id, 'recorded_by' => $cashier->id]);

        $this->actingAs($cashier)->patch(route('payments.void', $payment), [
            'reason' => 'Alasan percobaan oleh kasir yang tidak berwenang',
        ])->assertStatus(403);
    }

    /**
     * 4. Escaping output dan pencegahan serangan XSS pada tampilan Blade.
     */
    public function test_xss_payloads_are_strictly_escaped_on_views(): void
    {
        $cashier = User::factory()->kasir()->create();

        $xssCustomerName = '<script>alert("XSS-Customer")</script>';
        $customer = Customer::factory()->create([
            'name' => $xssCustomerName,
            'address' => '<img src=x onerror=alert(1)>',
        ]);

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'notes' => '<b>Catatan Tebal</b><script>evil()</script>',
        ]);

        // Verifikasi pada daftar pesanan
        $responseOrders = $this->actingAs($cashier)->get(route('orders.index'));
        $responseOrders->assertStatus(200);
        $responseOrders->assertDontSee($xssCustomerName, false);
        $responseOrders->assertSee('&lt;script&gt;alert(&quot;XSS-Customer&quot;)&lt;/script&gt;', false);

        // Verifikasi pada detail pesanan
        $responseDetail = $this->actingAs($cashier)->get(route('orders.show', $order));
        $responseDetail->assertStatus(200);
        $responseDetail->assertDontSee('<script>evil()</script>', false);
        $responseDetail->assertSee('&lt;script&gt;evil()&lt;/script&gt;', false);

        // Verifikasi pada cetak nota
        $responsePrint = $this->actingAs($cashier)->get(route('orders.print', $order));
        $responsePrint->assertStatus(200);
        $responsePrint->assertDontSee($xssCustomerName, false);
    }

    /**
     * 5. Kekebalan kueri terhadap SQL Injection pada fitur pencarian dan filter.
     */
    public function test_sql_injection_payloads_in_search_and_filters_execute_safely(): void
    {
        $cashier = User::factory()->kasir()->create();
        Customer::factory()->create(['name' => 'Budi Santoso', 'phone' => '081234567890']);

        // A. Pencarian Pelanggan dengan SQLi payload
        $sqliPayloads = [
            "' OR '1'='1",
            "'; DROP TABLE customers; --",
            "\" OR \"\"=\"",
            "1 UNION SELECT null, null, null--",
        ];

        foreach ($sqliPayloads as $payload) {
            $response = $this->actingAs($cashier)->get(route('customers.index', ['q' => $payload]));
            $response->assertStatus(200);
            // Database tetap utuh dan data tidak bocor
            $this->assertDatabaseHas('customers', ['name' => 'Budi Santoso']);
        }

        // B. Pencarian Pesanan dengan SQLi payload
        foreach ($sqliPayloads as $payload) {
            $response = $this->actingAs($cashier)->get(route('orders.index', ['search' => $payload]));
            $response->assertStatus(200);
        }
    }

    /**
     * 6. Keamanan unggah berkas (Logo Usaha): Menolak skrip PHP, executable, dan batas ukuran.
     */
    public function test_file_upload_security_rejects_malicious_files_and_oversize(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        // A. Unggah skrip PHP / berbahaya disamarkan
        $maliciousFile = UploadedFile::fake()->create('backdoor.php', 100, 'application/x-php');

        $responseMalicious = $this->actingAs($admin)->put(route('admin.settings.update'), [
            'business_name' => 'Laundry Aman',
            'business_address' => 'Alamat Aman',
            'business_phone' => '081234567890',
            'order_number_prefix' => 'LD',
            'default_estimated_hours' => 24,
            'cashier_max_discount_percent' => 10,
            'print_format' => 'thermal',
            'logo' => $maliciousFile,
        ]);
        $responseMalicious->assertSessionHasErrors('logo');

        // B. Unggah berkas gambar yang melebihi batas 1 MB (1024 KB)
        $oversizeImage = UploadedFile::fake()->create('giant_logo.png', 1050, 'image/png');

        $responseOversize = $this->actingAs($admin)->put(route('admin.settings.update'), [
            'business_name' => 'Laundry Aman',
            'business_address' => 'Alamat Aman',
            'business_phone' => '081234567890',
            'order_number_prefix' => 'LD',
            'default_estimated_hours' => 24,
            'cashier_max_discount_percent' => 10,
            'print_format' => 'thermal',
            'logo' => $oversizeImage,
        ]);
        $responseOversize->assertSessionHasErrors('logo');

        // C. Unggah berkas gambar sah (PNG valid, di bawah 1 MB)
        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAA=');
        $validImage = UploadedFile::fake()->createWithContent('valid_logo.png', $pngBytes);

        $responseValid = $this->actingAs($admin)->put(route('admin.settings.update'), [
            'business_name' => 'Laundry Aman',
            'business_address' => 'Alamat Aman',
            'business_phone' => '081234567890',
            'order_number_prefix' => 'LD',
            'default_estimated_hours' => 24,
            'cashier_max_discount_percent' => 10,
            'print_format' => 'thermal',
            'logo' => $validImage,
        ]);
        $responseValid->assertRedirect(route('admin.settings.index'));
        $responseValid->assertSessionHas('success');
    }

    /**
     * 7. Kerahasiaan data kredensial: Password dan token tidak bocor di serialisasi model atau log.
     */
    public function test_sensitive_passwords_and_tokens_are_never_leaked_in_model_or_logs(): void
    {
        $admin = User::factory()->admin()->create();

        // A. Model User toArray() & toJson() menyembunyikan password
        $userArray = $admin->toArray();
        $this->assertArrayNotHasKey('password', $userArray);
        $this->assertArrayNotHasKey('remember_token', $userArray);

        $userJson = $admin->toJson();
        $this->assertStringNotContainsString('password', $userJson);

        // B. Audit Log saat Reset Password tidak boleh mencatat kata sandi baru
        $targetUser = User::factory()->kasir()->create();
        $newPlainPassword = 'RahasiaSuperKuat2026!';

        $this->actingAs($admin)->patch(route('admin.users.reset-password', $targetUser), [
            'password' => $newPlainPassword,
            'password_confirmation' => $newPlainPassword,
        ]);

        $latestLog = ActivityLog::where('action', ActivityAction::UserPasswordReset)->latest('id')->first();
        $this->assertNotNull($latestLog);

        $logJson = json_encode($latestLog->metadata);
        $this->assertStringNotContainsString($newPlainPassword, $logJson);
        $this->assertStringNotContainsString('password', $logJson);
    }

    /**
     * 8. Keamanan Session dan Cookie: HttpOnly dan SameSite dikonfigurasi dengan aman.
     */
    public function test_session_cookie_configuration_meets_security_standards(): void
    {
        $httpOnly = Config::get('session.http_only');
        $sameSite = Config::get('session.same_site');

        // Cookie session wajib HttpOnly untuk mencegah pembacaan oleh JavaScript pihak luar
        $this->assertTrue($httpOnly, 'Konfigurasi session.http_only wajib bernilai true.');

        // SameSite wajib aktif (lax atau strict) untuk mencegah eksploitasi CSRF
        $this->assertContains($sameSite, ['lax', 'strict'], 'Konfigurasi session.same_site wajib bernilai lax atau strict.');
    }

    /**
     * 9. Halaman error tidak membocorkan data sensitif atau stack trace query.
     */
    public function test_error_pages_render_clean_user_friendly_messages_without_leaks(): void
    {
        $cashier = User::factory()->kasir()->create();

        // 403 Forbidden
        $response403 = $this->actingAs($cashier)->get(route('admin.audit-logs.index'));
        $response403->assertStatus(403);
        $response403->assertSee('Akses Halaman Ditolak');
        $response403->assertDontSee('SQLSTATE');
        $response403->assertDontSee('APP_KEY');

        // 404 Not Found
        $response404 = $this->actingAs($cashier)->get('/rute-tidak-ada-sama-sekali-xyz');
        $response404->assertStatus(404);
        $response404->assertSee('Halaman Tidak Ditemukan');
        $response404->assertDontSee('SQLSTATE');
    }
}
