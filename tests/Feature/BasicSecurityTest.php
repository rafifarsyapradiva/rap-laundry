<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BasicSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_csrf_protection_is_enforced_for_state_changing_requests(): void
    {
        $user = User::factory()->create();

        // 1. Verifikasi bahwa form login dan logout menyertakan token @csrf
        $this->get('/login')->assertSee('name="_token"', false);

        $loginResponse = $this->actingAs($user)->get('/dashboard');
        $loginResponse->assertSee('name="_token"', false);

        // 2. Verifikasi bahwa middleware CSRF menolak request POST tanpa token yang valid
        $middleware = new class(app(), app('encrypter')) extends ValidateCsrfToken {
            protected function runningUnitTests()
            {
                return false;
            }
        };

        $request = \Illuminate\Http\Request::create('/logout', 'POST');
        $request->setLaravelSession(session()->driver());

        $this->expectException(\Illuminate\Session\TokenMismatchException::class);
        $middleware->handle($request, fn () => response('OK'));
    }

    public function test_state_changing_operations_reject_get_method(): void
    {
        $user = User::factory()->create();

        // Operasi logout wajib menggunakan POST, pemanggilan via GET wajib ditolak 405
        $response = $this->actingAs($user)->get('/logout');

        $response->assertStatus(405);
    }

    public function test_blade_templates_escape_dangerous_xss_characters(): void
    {
        $user = User::factory()->create([
            'name' => '<script>alert("XSS")</script>',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        // Pastikan tag berbahaya ter-escape menjadi entitas HTML
        $response->assertSee('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', false);
        // Pastikan tidak ada raw HTML script yang lolos
        $response->assertDontSee('<script>alert("XSS")</script>', false);
    }

    public function test_eloquent_prevents_sql_injection_on_authentication(): void
    {
        User::factory()->create([
            'username' => 'admin_asli',
            'password' => Hash::make('password123'),
        ]);

        // Percobaan 1: Tautologi klasik SQL Injection
        $response = $this->post('/login', [
            'identity' => "' OR '1'='1",
            'password' => "' OR '1'='1",
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('identity');

        // Percobaan 2: Pemotongan kueri komentar SQL
        $response2 = $this->post('/login', [
            'identity' => "admin_asli' --",
            'password' => 'kata_sandi_acak',
        ]);

        $this->assertGuest();
        $response2->assertSessionHasErrors('identity');
    }

    public function test_cashier_is_forbidden_from_viewing_audit_logs(): void
    {
        $cashier = User::factory()->kasir()->create();

        $response = $this->actingAs($cashier)->get('/admin/audit-logs');

        $response->assertStatus(403);
        $response->assertSee('Akses Halaman Ditolak');
    }

    public function test_cashier_is_forbidden_from_exporting_reports(): void
    {
        $cashier = User::factory()->kasir()->create();

        $response = $this->actingAs($cashier)->get('/admin/reports/export');

        $response->assertStatus(403);
        $response->assertSee('Akses Halaman Ditolak');
    }

    public function test_admin_can_view_audit_logs(): void
    {
        $admin = User::factory()->admin()->create();

        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => ActivityAction::AuthLogin,
            'description' => 'Admin masuk untuk pemeriksaan audit keamanan',
            'metadata' => ['ip_address' => '127.0.0.1'],
        ]);

        $response = $this->actingAs($admin)->get('/admin/audit-logs');

        $response->assertStatus(200);
        $response->assertSee('Jejak Audit Aktivitas Sistem');
        $response->assertSee('Admin masuk untuk pemeriksaan audit keamanan');
    }

    public function test_admin_can_access_report_export(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/reports/export');

        $response->assertStatus(200);
        $response->assertSee('Ekspor Laporan Keuangan');
    }

    public function test_gates_protect_audit_logs_and_financial_exports(): void
    {
        $admin = User::factory()->admin()->create();
        $cashier = User::factory()->kasir()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('view-audit-logs'));
        $this->assertFalse(Gate::forUser($cashier)->allows('view-audit-logs'));

        $this->assertTrue(Gate::forUser($admin)->allows('export-financial-reports'));
        $this->assertFalse(Gate::forUser($cashier)->allows('export-financial-reports'));
    }
}
