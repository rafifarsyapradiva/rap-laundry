<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogViewerTest extends TestCase
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
    }

    public function test_guest_cannot_view_audit_logs_and_is_redirected(): void
    {
        $response = $this->get(route('admin.audit-logs.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_cashier_cannot_view_audit_logs_and_gets_403(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('admin.audit-logs.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_audit_logs_with_pagination(): void
    {
        // Buat 20 log aktivitas
        for ($i = 1; $i <= 20; $i++) {
            ActivityLog::create([
                'user_id' => $this->admin->id,
                'action' => ActivityAction::OrderCreated,
                'description' => "Pesanan #{$i} berhasil dibuat di sistem.",
                'metadata' => ['order_index' => $i],
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('admin.audit-logs.index'));

        $response->assertOk();
        $response->assertSee('Jejak Audit Aktivitas Sistem');
        $response->assertSee('Pesanan #20 berhasil dibuat');
        // Pastikan pagination 15 item bekerja (item #5 ada di page 2, bukan di page 1)
        $response->assertDontSee('Pesanan #5 berhasil dibuat');
    }

    public function test_admin_can_filter_logs_by_action_type(): void
    {
        ActivityLog::create([
            'user_id' => $this->admin->id,
            'action' => ActivityAction::PaymentRecorded,
            'description' => 'Pembayaran tunai Rp50.000 dicatat.',
        ]);

        ActivityLog::create([
            'user_id' => $this->admin->id,
            'action' => ActivityAction::PaymentVoided,
            'description' => 'Pembayaran #10 dibatalkan (void).',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.audit-logs.index', [
            'action' => ActivityAction::PaymentVoided->value,
        ]));

        $response->assertOk();
        $response->assertSee('Pembayaran #10 dibatalkan (void).');
        $response->assertDontSee('Pembayaran tunai Rp50.000 dicatat.');
    }

    public function test_admin_can_filter_logs_by_actor_user(): void
    {
        $otherCashier = User::factory()->create([
            'name' => 'Siti Kasir Dua',
            'role' => UserRole::Kasir,
        ]);

        ActivityLog::create([
            'user_id' => $this->admin->id,
            'action' => ActivityAction::SettingUpdated,
            'description' => 'Admin mengubah nama usaha.',
        ]);

        ActivityLog::create([
            'user_id' => $otherCashier->id,
            'action' => ActivityAction::OrderCreated,
            'description' => 'Siti membuat pesanan baru.',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.audit-logs.index', [
            'user_id' => $otherCashier->id,
        ]));

        $response->assertOk();
        $response->assertSee('Siti membuat pesanan baru.');
        $response->assertDontSee('Admin mengubah nama usaha.');
    }

    public function test_admin_can_filter_logs_by_date_range(): void
    {
        $logOld = ActivityLog::create([
            'user_id' => $this->admin->id,
            'action' => ActivityAction::AuthLogin,
            'description' => 'Login admin pada 5 hari lalu.',
        ]);
        $logOld->timestamps = false;
        $logOld->created_at = now()->subDays(5);
        $logOld->save();

        $logToday = ActivityLog::create([
            'user_id' => $this->admin->id,
            'action' => ActivityAction::AuthLogin,
            'description' => 'Login admin pada hari ini.',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.audit-logs.index', [
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertSee('Login admin pada hari ini.');
        $response->assertDontSee('Login admin pada 5 hari lalu.');
    }

    public function test_admin_can_search_logs_by_keyword_description(): void
    {
        ActivityLog::create([
            'user_id' => $this->admin->id,
            'action' => ActivityAction::ServicePriceChanged,
            'description' => 'Tarif layanan Cuci Bedcover diubah.',
        ]);

        ActivityLog::create([
            'user_id' => $this->admin->id,
            'action' => ActivityAction::ServiceCreated,
            'description' => 'Layanan Setrika Express ditambahkan.',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.audit-logs.index', [
            'search' => 'Bedcover',
        ]));

        $response->assertOk();
        $response->assertSee('Tarif layanan Cuci Bedcover diubah.');
        $response->assertDontSee('Layanan Setrika Express ditambahkan.');
    }

    public function test_sensitive_passwords_and_tokens_are_never_exposed_in_logs(): void
    {
        $secretPassword = 'SuperSecretPassword123!';

        // Simulasi reset password melalui UserService atau controller
        $user = User::factory()->create();
        $userService = app(\App\Services\UserService::class);
        $userService->resetPassword($user, $secretPassword, $this->admin);

        $log = ActivityLog::where('action', ActivityAction::UserPasswordReset)->latest()->first();

        $this->assertNotNull($log);
        $this->assertStringNotContainsString($secretPassword, $log->description);
        $this->assertStringNotContainsString(bcrypt($secretPassword), json_encode($log->metadata));
        $this->assertArrayNotHasKey('password', $log->metadata);
        $this->assertArrayNotHasKey('password_confirmation', $log->metadata);
        $this->assertArrayNotHasKey('token', $log->metadata);
    }
}
