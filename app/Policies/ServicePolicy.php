<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat daftar master layanan.
     * Kasir dan Admin diizinkan melihat daftar layanan.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Tentukan apakah pengguna dapat melihat detail master layanan.
     */
    public function view(User $user, Service $service): bool
    {
        return $user->is_active;
    }

    /**
     * Tentukan apakah pengguna dapat membuat master layanan baru.
     * Hanya Pemilik / Administrator yang berwenang.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Tentukan apakah pengguna dapat memperbarui master layanan.
     * Hanya Pemilik / Administrator yang berwenang.
     */
    public function update(User $user, Service $service): bool
    {
        return $user->isAdmin();
    }

    /**
     * Tentukan apakah pengguna dapat mengarsipkan master layanan.
     * Hanya Pemilik / Administrator yang berwenang.
     */
    public function delete(User $user, Service $service): bool
    {
        return $user->isAdmin();
    }

    /**
     * Tentukan apakah pengguna dapat memulihkan master layanan dari arsip.
     * Hanya Pemilik / Administrator yang berwenang.
     */
    public function restore(User $user, Service $service): bool
    {
        return $user->isAdmin();
    }

    /**
     * Tentukan apakah pengguna dapat menghapus permanen master layanan.
     * Hanya Pemilik / Administrator yang berwenang.
     */
    public function forceDelete(User $user, Service $service): bool
    {
        return $user->isAdmin();
    }
}
