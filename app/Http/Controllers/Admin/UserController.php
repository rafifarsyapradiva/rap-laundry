<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResetUserPasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Tampilkan daftar seluruh pengguna/staf dengan filter peran dan status.
     */
    public function index(Request $request): View
    {
        $query = User::query()->latest('id');

        // Filter Peran (Role)
        if ($request->filled('role')) {
            $roleValue = $request->input('role');
            if (UserRole::tryFrom($roleValue)) {
                $query->where('role', $roleValue);
            }
        }

        // Filter Status Aktif
        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', (bool) $request->input('is_active'));
        }

        // Pencarian Kata Kunci
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(15)->withQueryString();
        $roles = UserRole::cases();

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Tampilkan formulir pembuatan pengguna baru.
     */
    public function create(): View
    {
        $roles = UserRole::cases();

        return view('admin.users.create', compact('roles'));
    }

    /**
     * Simpan pengguna baru ke dalam database.
     */
    public function store(StoreUserRequest $request, UserService $userService): RedirectResponse
    {
        $user = $userService->createUser(
            data: $request->validated(),
            actor: $request->user()
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Pengguna {$user->name} (@{$user->username}) berhasil ditambahkan.");
    }

    /**
     * Tampilkan formulir pengubahan data pengguna.
     */
    public function edit(User $user): View
    {
        $roles = UserRole::cases();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Perbarui data profil pengguna.
     */
    public function update(
        UpdateUserRequest $request,
        User $user,
        UserService $userService
    ): RedirectResponse {
        try {
            $userService->updateUser(
                targetUser: $user,
                data: $request->validated(),
                actor: $request->user()
            );

            return redirect()
                ->route('admin.users.index')
                ->with('success', "Data pengguna {$user->name} berhasil diperbarui.");
        } catch (DomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['role' => $e->getMessage()]);
        }
    }

    /**
     * Alihkan status aktif/nonaktif akun dengan proteksi anti self-lockout.
     */
    public function toggleStatus(User $user, UserService $userService): RedirectResponse
    {
        try {
            $newStatus = $userService->toggleStatus($user, auth()->user());
            $label = $newStatus ? 'diaktifkan kembali' : 'dinonaktifkan';

            return redirect()
                ->route('admin.users.index')
                ->with('success', "Akun pengguna {$user->name} berhasil {$label}.");
        } catch (DomainException $e) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Reset kata sandi pengguna secara aman.
     */
    public function resetPassword(
        ResetUserPasswordRequest $request,
        User $user,
        UserService $userService
    ): RedirectResponse {
        $userService->resetPassword(
            targetUser: $user,
            newPassword: $request->validated('password'),
            actor: $request->user()
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Kata sandi untuk pengguna {$user->name} (@{$user->username}) berhasil disetel ulang.");
    }
}
