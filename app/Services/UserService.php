<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Tambah pengguna baru ke sistem, hash password, dan rekam jejak audit aktivitas.
     *
     * @param  array<string, mixed>  $data
     * @param  User                  $actor
     * @return User
     */
    public function createUser(array $data, User $actor): User
    {
        return DB::transaction(function () use ($data, $actor) {
            $user = User::create([
                'name' => trim((string) $data['name']),
                'username' => strtolower(trim((string) $data['username'])),
                'email' => strtolower(trim((string) $data['email'])),
                'password' => Hash::make($data['password']),
                'role' => $data['role'] instanceof UserRole ? $data['role'] : UserRole::from($data['role']),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => ActivityAction::UserCreated,
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'description' => "Pengguna baru '{$user->name}' (@{$user->username}) dengan peran {$user->role->label()} ditambahkan oleh {$actor->name}.",
                'metadata' => [
                    'created_user_id' => $user->id,
                    'created_username' => $user->username,
                    'role' => $user->role->value,
                    'is_active' => $user->is_active,
                    'actor_username' => $actor->username,
                ],
            ]);

            return $user;
        });
    }

    /**
     * Perbarui data profil pengguna dengan proteksi self-lockout dan jejak audit.
     *
     * @param  User                  $targetUser
     * @param  array<string, mixed>  $data
     * @param  User                  $actor
     * @return User
     */
    public function updateUser(User $targetUser, array $data, User $actor): User
    {
        return DB::transaction(function () use ($targetUser, $data, $actor) {
            $newRole = $data['role'] instanceof UserRole ? $data['role'] : UserRole::from($data['role']);
            $newActive = (bool) $data['is_active'];

            // Proteksi 1: Admin tidak boleh menonaktifkan akunnya sendiri
            if ($actor->id === $targetUser->id && ! $newActive) {
                throw new DomainException('Anda tidak dapat menonaktifkan akun Administrator Anda sendiri.');
            }

            // Proteksi 2: Admin tidak boleh menurunkan peran sendiri jika ia adalah admin aktif terakhir
            if ($actor->id === $targetUser->id && $targetUser->isAdmin() && $newRole !== UserRole::Admin) {
                $otherActiveAdmins = User::query()
                    ->where('role', UserRole::Admin)
                    ->where('is_active', true)
                    ->where('id', '!=', $targetUser->id)
                    ->count();

                if ($otherActiveAdmins === 0) {
                    throw new DomainException('Anda tidak dapat menurunkan hak akses akun sendiri karena tidak ada Administrator aktif lainnya di sistem.');
                }
            }

            // Proteksi 3: Mencegah penonaktifan admin aktif terakhir oleh siapa pun
            if ($targetUser->isAdmin() && $targetUser->is_active && ! $newActive) {
                $otherActiveAdmins = User::query()
                    ->where('role', UserRole::Admin)
                    ->where('is_active', true)
                    ->where('id', '!=', $targetUser->id)
                    ->count();

                if ($otherActiveAdmins === 0) {
                    throw new DomainException('Tidak dapat menonaktifkan akun Administrator terakhir di sistem.');
                }
            }

            $oldData = [
                'name' => $targetUser->name,
                'username' => $targetUser->username,
                'email' => $targetUser->email,
                'role' => $targetUser->role->value,
                'is_active' => $targetUser->is_active,
            ];

            $targetUser->update([
                'name' => trim((string) $data['name']),
                'username' => strtolower(trim((string) $data['username'])),
                'email' => strtolower(trim((string) $data['email'])),
                'role' => $newRole,
                'is_active' => $newActive,
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => ActivityAction::UserUpdated,
                'subject_type' => User::class,
                'subject_id' => $targetUser->id,
                'description' => "Data pengguna '{$targetUser->name}' (@{$targetUser->username}) diperbarui oleh {$actor->name}.",
                'metadata' => [
                    'target_user_id' => $targetUser->id,
                    'target_username' => $targetUser->username,
                    'old_data' => $oldData,
                    'new_data' => [
                        'name' => $targetUser->name,
                        'username' => $targetUser->username,
                        'email' => $targetUser->email,
                        'role' => $targetUser->role->value,
                        'is_active' => $targetUser->is_active,
                    ],
                    'actor_username' => $actor->username,
                ],
            ]);

            return $targetUser;
        });
    }

    /**
     * Alihkan status aktif/nonaktif akun dengan proteksi anti self-lockout.
     *
     * @param  User  $targetUser
     * @param  User  $actor
     * @return bool  Status baru (true = aktif, false = nonaktif)
     */
    public function toggleStatus(User $targetUser, User $actor): bool
    {
        return DB::transaction(function () use ($targetUser, $actor) {
            // Proteksi 1: Admin tidak dapat menonaktifkan dirinya sendiri
            if ($actor->id === $targetUser->id) {
                throw new DomainException('Anda tidak dapat menonaktifkan akun Administrator Anda sendiri.');
            }

            // Proteksi 2: Mencegah penonaktifan admin aktif terakhir
            if ($targetUser->isAdmin() && $targetUser->is_active) {
                $otherActiveAdmins = User::query()
                    ->where('role', UserRole::Admin)
                    ->where('is_active', true)
                    ->where('id', '!=', $targetUser->id)
                    ->count();

                if ($otherActiveAdmins === 0) {
                    throw new DomainException('Tidak dapat menonaktifkan akun Administrator terakhir di sistem.');
                }
            }

            $oldStatus = $targetUser->is_active;
            $newStatus = ! $oldStatus;
            $targetUser->is_active = $newStatus;
            $targetUser->save();

            $statusLabel = $newStatus ? 'diaktifkan' : 'dinonaktifkan';

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => ActivityAction::UserStatusChanged,
                'subject_type' => User::class,
                'subject_id' => $targetUser->id,
                'description' => "Status akun pengguna '{$targetUser->name}' (@{$targetUser->username}) {$statusLabel} oleh {$actor->name}.",
                'metadata' => [
                    'target_user_id' => $targetUser->id,
                    'target_username' => $targetUser->username,
                    'from_status' => $oldStatus,
                    'to_status' => $newStatus,
                    'actor_username' => $actor->username,
                ],
            ]);

            return $newStatus;
        });
    }

    /**
     * Reset kata sandi pengguna secara aman dan rekam jejak audit tanpa membocorkan kata sandi.
     *
     * @param  User    $targetUser
     * @param  string  $newPassword
     * @param  User    $actor
     * @return void
     */
    public function resetPassword(User $targetUser, string $newPassword, User $actor): void
    {
        DB::transaction(function () use ($targetUser, $newPassword, $actor) {
            $targetUser->password = Hash::make($newPassword);
            $targetUser->save();

            // PENTING: Dilarang keras menyimpan password baru (baik hash apalagi teks biasa) ke dalam log
            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => ActivityAction::UserPasswordReset,
                'subject_type' => User::class,
                'subject_id' => $targetUser->id,
                'description' => "Kata sandi pengguna '{$targetUser->name}' (@{$targetUser->username}) berhasil direset oleh {$actor->name}.",
                'metadata' => [
                    'target_user_id' => $targetUser->id,
                    'target_username' => $targetUser->username,
                    'actor_username' => $actor->username,
                ],
            ]);
        });
    }
}
