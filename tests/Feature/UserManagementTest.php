<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'username' => 'superadmin',
            'email' => 'superadmin@raplaundry.local',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create([
            'username' => 'kasir1',
            'email' => 'kasir1@raplaundry.local',
            'role' => UserRole::Kasir,
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_user_management_and_is_redirected(): void
    {
        $response = $this->get(route('admin.users.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_cashier_cannot_access_user_management_and_gets_403(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('admin.users.index'));
        $response->assertForbidden();

        $responseCreate = $this->actingAs($this->cashier)->get(route('admin.users.create'));
        $responseCreate->assertForbidden();

        $responseStore = $this->actingAs($this->cashier)->post(route('admin.users.store'), []);
        $responseStore->assertForbidden();
    }

    public function test_admin_can_view_users_list_with_roles_and_statuses(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('Manajemen Pengguna dan Hak Akses');
        $response->assertSee('superadmin');
        $response->assertSee('kasir1');
        $response->assertSee('Admin');
        $response->assertSee('Kasir');
    }

    public function test_admin_can_create_new_cashier_user_with_hashed_password(): void
    {
        $payload = [
            'name' => 'Budi Staf Kasir',
            'username' => 'budi_kasir',
            'email' => 'budi@raplaundry.local',
            'password' => 'BudiPassword123!',
            'password_confirmation' => 'BudiPassword123!',
            'role' => UserRole::Kasir->value,
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), $payload);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $newUser = User::where('username', 'budi_kasir')->first();
        $this->assertNotNull($newUser);
        $this->assertSame('Budi Staf Kasir', $newUser->name);
        $this->assertSame(UserRole::Kasir, $newUser->role);
        $this->assertTrue($newUser->is_active);
        $this->assertTrue(Hash::check('BudiPassword123!', $newUser->password));

        // Audit log tercatat
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => ActivityAction::UserCreated->value,
            'subject_id' => $newUser->id,
        ]);
    }

    public function test_duplicate_username_and_email_are_rejected(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Kloning Admin',
            'username' => 'superadmin', // Sudah dipakai
            'email' => 'superadmin@raplaundry.local', // Sudah dipakai
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => UserRole::Kasir->value,
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors(['username', 'email']);
    }

    public function test_password_shorter_than_8_chars_or_unconfirmed_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Staf Baru',
            'username' => 'staf_baru',
            'email' => 'staf@raplaundry.local',
            'password' => 'pendek',
            'password_confirmation' => 'beda_konfirmasi',
            'role' => UserRole::Kasir->value,
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_admin_can_update_user_profile(): void
    {
        $payload = [
            'name' => 'Kasir Senior Hebat',
            'username' => 'kasir1_updated',
            'email' => 'kasir1_updated@raplaundry.local',
            'role' => UserRole::Kasir->value,
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.users.update', $this->cashier), $payload);

        $response->assertRedirect(route('admin.users.index'));
        $this->cashier->refresh();

        $this->assertSame('Kasir Senior Hebat', $this->cashier->name);
        $this->assertSame('kasir1_updated', $this->cashier->username);
        $this->assertSame('kasir1_updated@raplaundry.local', $this->cashier->email);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => ActivityAction::UserUpdated->value,
            'subject_id' => $this->cashier->id,
        ]);
    }

    public function test_admin_can_toggle_user_active_status(): void
    {
        $this->assertTrue($this->cashier->is_active);

        // Nonaktifkan kasir
        $response = $this->actingAs($this->admin)->patch(route('admin.users.toggle-status', $this->cashier));
        $response->assertRedirect(route('admin.users.index'));
        $this->cashier->refresh();
        $this->assertFalse($this->cashier->is_active);

        // Aktifkan kembali kasir
        $response2 = $this->actingAs($this->admin)->patch(route('admin.users.toggle-status', $this->cashier));
        $response2->assertRedirect(route('admin.users.index'));
        $this->cashier->refresh();
        $this->assertTrue($this->cashier->is_active);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => ActivityAction::UserStatusChanged->value,
            'subject_id' => $this->cashier->id,
        ]);
    }

    public function test_admin_can_reset_user_password_securely(): void
    {
        $response = $this->actingAs($this->admin)->patch(route('admin.users.reset-password', $this->cashier), [
            'password' => 'PasswordBaruKasir123!',
            'password_confirmation' => 'PasswordBaruKasir123!',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $this->cashier->refresh();
        $this->assertTrue(Hash::check('PasswordBaruKasir123!', $this->cashier->password));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => ActivityAction::UserPasswordReset->value,
            'subject_id' => $this->cashier->id,
        ]);
    }

    public function test_admin_cannot_deactivate_their_own_account_self_lockout(): void
    {
        // 1. Coba toggle status akun sendiri
        $response = $this->actingAs($this->admin)->patch(route('admin.users.toggle-status', $this->admin));
        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error');

        $this->admin->refresh();
        $this->assertTrue($this->admin->is_active);

        // 2. Coba ubah is_active=0 via form update
        $responseUpdate = $this->actingAs($this->admin)->put(route('admin.users.update', $this->admin), [
            'name' => $this->admin->name,
            'username' => $this->admin->username,
            'email' => $this->admin->email,
            'role' => UserRole::Admin->value,
            'is_active' => '0',
        ]);

        $responseUpdate->assertSessionHasErrors(['role']);
        $this->admin->refresh();
        $this->assertTrue($this->admin->is_active);
    }

    public function test_admin_cannot_demote_themselves_if_they_are_the_last_active_admin(): void
    {
        // $this->admin adalah satu-satunya admin aktif
        $response = $this->actingAs($this->admin)->put(route('admin.users.update', $this->admin), [
            'name' => $this->admin->name,
            'username' => $this->admin->username,
            'email' => $this->admin->email,
            'role' => UserRole::Kasir->value, // Turunkan jadi kasir
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors(['role']);
        $this->admin->refresh();
        $this->assertSame(UserRole::Admin, $this->admin->role);
    }

    public function test_last_active_admin_cannot_be_deactivated(): void
    {
        // Buat admin kedua yang akan mencoba menonaktifkan admin pertama
        $adminTwo = User::factory()->create([
            'username' => 'admintwo',
            'email' => 'admintwo@raplaundry.local',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        // Nonaktifkan admin pertama oleh admin kedua (diizinkan karena masih ada adminTwo)
        $response1 = $this->actingAs($adminTwo)->patch(route('admin.users.toggle-status', $this->admin));
        $response1->assertRedirect(route('admin.users.index'));
        $this->admin->refresh();
        $this->assertFalse($this->admin->is_active);

        // Sekarang adminTwo adalah SATU-SATUNYA admin aktif. Coba nonaktifkan adminTwo via update
        $response2 = $this->actingAs($adminTwo)->put(route('admin.users.update', $adminTwo), [
            'name' => $adminTwo->name,
            'username' => $adminTwo->username,
            'email' => $adminTwo->email,
            'role' => UserRole::Admin->value,
            'is_active' => '0',
        ]);

        $response2->assertSessionHasErrors(['role']);
        $adminTwo->refresh();
        $this->assertTrue($adminTwo->is_active);
    }

    public function test_deactivated_user_cannot_login(): void
    {
        $this->cashier->update(['is_active' => false]);

        $response = $this->post(route('login.store'), [
            'identity' => $this->cashier->username,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['identity']);
        $this->assertGuest();
    }
}
