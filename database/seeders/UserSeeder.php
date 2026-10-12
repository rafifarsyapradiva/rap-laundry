<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * PERINGATAN KEAMANAN:
     * Password bawaan (admin123 & kasir123) hanya untuk keperluan pengujian dan demonstrasi lokal.
     * Pada instalasi produksi di lingkungan kasir nyata:
     * 1. Ubah variabel DEFAULT_ADMIN_PASSWORD & DEFAULT_KASIR_PASSWORD di file .env, ATAU
     * 2. Jalankan perintah Artisan: `php artisan laundry:reset-admin` untuk menyetel password baru yang kuat.
     */
    public function run(): void
    {
        $adminPassword = env('DEFAULT_ADMIN_PASSWORD', 'admin123');
        $kasirPassword = env('DEFAULT_KASIR_PASSWORD', 'kasir123');

        // Akun Admin Utama (Pemilik)
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Pemilik Laundry',
                'email' => 'admin@raplaundry.local',
                'password' => Hash::make($adminPassword),
                'role' => UserRole::Admin,
                'is_active' => true,
            ]
        );

        // Akun Kasir Operasional (Staf)
        User::updateOrCreate(
            ['username' => 'kasir'],
            [
                'name' => 'Kasir Operasional',
                'email' => 'kasir@raplaundry.local',
                'password' => Hash::make($kasirPassword),
                'role' => UserRole::Kasir,
                'is_active' => true,
            ]
        );
    }
}
