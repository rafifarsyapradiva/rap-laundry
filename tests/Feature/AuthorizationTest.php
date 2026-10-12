<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_access_operational_dashboard(): void
    {
        $cashier = User::factory()->kasir()->create([
            'name' => 'Budi Kasir',
            'is_active' => true,
        ]);

        $response = $this->actingAs($cashier)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Budi Kasir');
        $response->assertSee('Kasir');
    }

    public function test_admin_can_access_operational_dashboard(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Pak Bos',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Pak Bos');
        $response->assertSee('Admin');
    }

    public function test_cashier_is_forbidden_from_accessing_admin_user_management_url(): void
    {
        $cashier = User::factory()->kasir()->create(['is_active' => true]);

        $response = $this->actingAs($cashier)->get('/admin/users');

        $response->assertStatus(403);
        $response->assertSee('Akses Halaman Ditolak');
        $response->assertSee('403');
    }

    public function test_cashier_is_forbidden_from_accessing_admin_settings_url(): void
    {
        $cashier = User::factory()->kasir()->create(['is_active' => true]);

        $response = $this->actingAs($cashier)->get('/admin/settings');

        $response->assertStatus(403);
        $response->assertSee('Akses Halaman Ditolak');
    }

    public function test_admin_can_access_user_management_url(): void
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertStatus(200);
        $response->assertSee('Daftar Pengguna dan Staf Operasional');
    }

    public function test_admin_can_access_settings_url(): void
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->get('/admin/settings');

        $response->assertStatus(200);
        $response->assertSee('Konfigurasi Usaha dan Cetak Nota');
    }

    public function test_gates_authorize_admin_and_deny_cashier(): void
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $cashier = User::factory()->kasir()->create(['is_active' => true]);

        // Gate manage-users
        $this->assertTrue(Gate::forUser($admin)->allows('manage-users'));
        $this->assertFalse(Gate::forUser($cashier)->allows('manage-users'));

        // Gate manage-settings
        $this->assertTrue(Gate::forUser($admin)->allows('manage-settings'));
        $this->assertFalse(Gate::forUser($cashier)->allows('manage-settings'));

        // Gate access-operational
        $this->assertTrue(Gate::forUser($admin)->allows('access-operational'));
        $this->assertTrue(Gate::forUser($cashier)->allows('access-operational'));
    }

    public function test_user_policy_prevents_admin_from_deleting_their_own_account(): void
    {
        $admin1 = User::factory()->admin()->create(['is_active' => true]);
        $admin2 = User::factory()->admin()->create(['is_active' => true]);
        $cashier = User::factory()->kasir()->create(['is_active' => true]);

        // Admin dapat menghapus staf atau admin lain
        $this->assertTrue($admin1->can('delete', $admin2));
        $this->assertTrue($admin1->can('delete', $cashier));

        // Admin dilarang menghapus akunnya sendiri
        $this->assertFalse($admin1->can('delete', $admin1));

        // Kasir dilarang menghapus akun siapapun
        $this->assertFalse($cashier->can('delete', $admin1));
        $this->assertFalse($cashier->can('delete', $cashier));
    }

    public function test_inactive_user_is_logged_out_by_role_middleware(): void
    {
        $inactiveUser = User::factory()->kasir()->inactive()->create();

        $response = $this->actingAs($inactiveUser)->get('/dashboard');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('identity');
    }

    public function test_reset_admin_artisan_command_executes_successfully(): void
    {
        $this->artisan('laundry:reset-admin', [
            '--username' => 'superadmin',
            '--password' => 'supersecret123',
            '--name' => 'Owner Utama',
        ])
            ->expectsOutputToContain('Akun Admin [superadmin] berhasil disetel ulang')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'username' => 'superadmin',
            'name' => 'Owner Utama',
            'role' => UserRole::Admin->value,
            'is_active' => true,
        ]);

        $createdUser = User::where('username', 'superadmin')->first();
        $this->assertNotNull($createdUser);
        $this->assertTrue(Hash::check('supersecret123', $createdUser->password));
    }
}
