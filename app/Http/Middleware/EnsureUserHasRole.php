<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Pastikan pengguna telah terautentikasi
        if (! $user) {
            return redirect()->route('login');
        }

        // Jika akun dinonaktifkan di tengah sesi, paksa logout dan invalidasi
        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'identity' => 'Akun Anda sedang dinonaktifkan. Silakan hubungi Pemilik/Administrator.',
            ]);
        }

        // Petakan string role yang diizinkan ke nilai enum UserRole
        $allowedValues = [];
        foreach ($roles as $roleParam) {
            foreach (explode(',', $roleParam) as $singleRole) {
                $trimmed = trim($singleRole);
                $enumRole = UserRole::tryFrom($trimmed);
                if ($enumRole) {
                    $allowedValues[] = $enumRole;
                }
            }
        }

        // Tolak jika peran pengguna saat ini tidak terdaftar dalam peran yang diizinkan
        if (! in_array($user->role, $allowedValues, true)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki hak akses untuk membuka halaman ini.');
        }

        return $next($request);
    }
}
