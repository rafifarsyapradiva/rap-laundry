<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat daftar pelanggan.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Tentukan apakah pengguna dapat melihat detail pelanggan.
     */
    public function view(User $user, Customer $customer): bool
    {
        return $user->is_active;
    }

    /**
     * Tentukan apakah pengguna dapat menambah data pelanggan baru.
     */
    public function create(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Tentukan apakah pengguna dapat memperbarui data pelanggan.
     */
    public function update(User $user, Customer $customer): bool
    {
        return $user->is_active;
    }

    /**
     * Tentukan apakah pengguna dapat mengarsipkan data pelanggan.
     */
    public function delete(User $user, Customer $customer): bool
    {
        return $user->is_active;
    }

    /**
     * Tentukan apakah pengguna dapat memulihkan pelanggan dari arsip.
     */
    public function restore(User $user, Customer $customer): bool
    {
        return $user->is_active;
    }

    /**
     * Tentukan apakah pengguna dapat menghapus permanen data pelanggan.
     * Hanya Pemilik / Admin yang berwenang melakukan force-delete.
     */
    public function forceDelete(User $user, Customer $customer): bool
    {
        return $user->isAdmin();
    }
}
