<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ActivityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Tampilkan halaman formulir login.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Proses autentikasi pengguna masuk ke sistem.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $identity = trim($request->input('identity'));
        $password = (string) $request->input('password');
        $remember = $request->boolean('remember');

        // Tentukan apakah input berupa email atau username
        $field = filter_var($identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Periksa apakah akun terdaftar namun berstatus dinonaktifkan
        $candidateUser = User::where($field, $identity)->first();

        if ($candidateUser && ! $candidateUser->is_active) {
            $request->hitRateLimiter();

            throw ValidationException::withMessages([
                'identity' => 'Akun Anda sedang dinonaktifkan. Silakan hubungi Pemilik/Administrator.',
            ]);
        }

        // Jalankan autentikasi dengan password hashing bawaan Laravel
        if (! Auth::attempt([$field => $identity, 'password' => $password, 'is_active' => true], $remember)) {
            $request->hitRateLimiter();

            throw ValidationException::withMessages([
                'identity' => 'Nama pengguna/email atau kata sandi yang Anda masukkan salah.',
            ]);
        }

        // Bersihkan batas percobaan setelah sukses
        $request->clearRateLimiter();

        // Regenerasi session ID untuk mencegah serangan session fixation
        $request->session()->regenerate();

        $user = Auth::user();

        // Rekam jejak audit aktivitas login
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => ActivityAction::AuthLogin,
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'description' => "Pengguna {$user->name} ({$user->username}) berhasil masuk ke sistem.",
            'metadata' => [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        ]);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Proses keluar dari sistem (logout) dan invalidasi sesi.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            // Rekam jejak audit aktivitas logout
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => ActivityAction::AuthLogout,
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'description' => "Pengguna {$user->name} ({$user->username}) telah keluar dari sistem.",
                'metadata' => [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);
        }

        // Invalidasi sesi dan regenerasi token CSRF
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah berhasil keluar dari sistem.');
    }
}
