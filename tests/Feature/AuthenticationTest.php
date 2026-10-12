<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('RAP Laundry');
        $response->assertSee('Masuk ke Sistem');
        $response->assertSee('Email atau Nama Pengguna');
    }

    public function test_users_can_authenticate_using_email(): void
    {
        $user = User::factory()->create([
            'email' => 'kasir1@raplaundry.local',
            'password' => Hash::make('rahasia123'),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'identity' => 'kasir1@raplaundry.local',
            'password' => 'rahasia123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => ActivityAction::AuthLogin->value,
            'subject_id' => $user->id,
        ]);
    }

    public function test_users_can_authenticate_using_username(): void
    {
        $user = User::factory()->create([
            'username' => 'kasir_hebat',
            'password' => Hash::make('rahasia123'),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'identity' => 'kasir_hebat',
            'password' => 'rahasia123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => ActivityAction::AuthLogin->value,
            'subject_id' => $user->id,
        ]);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'username' => 'staf_laundry',
            'password' => Hash::make('rahasia123'),
            'is_active' => true,
        ]);

        $response = $this->from('/login')->post('/login', [
            'identity' => 'staf_laundry',
            'password' => 'password_salah',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('identity');
    }

    public function test_users_can_not_authenticate_if_inactive(): void
    {
        $user = User::factory()->inactive()->create([
            'username' => 'staf_nonaktif',
            'password' => Hash::make('rahasia123'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'identity' => 'staf_nonaktif',
            'password' => 'rahasia123',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('identity');

        $errors = session('errors')->get('identity');
        $this->assertStringContainsString('dinonaktifkan', $errors[0]);
    }

    public function test_login_attempts_are_throttled_after_too_many_failures(): void
    {
        $user = User::factory()->create([
            'username' => 'target_throttle',
            'password' => Hash::make('rahasia123'),
        ]);

        // Kirim 5 percobaan gagal
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'identity' => 'target_throttle',
                'password' => 'salah_terus',
            ]);
        }

        // Percobaan ke-6 harus di-lockout
        $response = $this->post('/login', [
            'identity' => 'target_throttle',
            'password' => 'salah_terus',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('identity');

        $errors = session('errors')->get('identity');
        $this->assertStringContainsString('Terlalu banyak percobaan login', $errors[0]);
    }

    public function test_session_is_regenerated_upon_successful_login(): void
    {
        $user = User::factory()->create([
            'username' => 'user_session_test',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $this->get('/login');
        $initialSessionId = session()->getId();

        $this->post('/login', [
            'identity' => 'user_session_test',
            'password' => 'password123',
        ]);

        $newSessionId = session()->getId();
        $this->assertNotSame($initialSessionId, $newSessionId);
    }

    public function test_users_can_logout_and_session_is_invalidated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Anda telah berhasil keluar dari sistem.');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => ActivityAction::AuthLogout->value,
            'subject_id' => $user->id,
        ]);
    }

    public function test_unauthenticated_users_cannot_access_internal_routes(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_are_redirected_away_from_login_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect(route('dashboard'));
    }
}
