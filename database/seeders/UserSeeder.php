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
     */
    public function run(): void
    {
        // Akun Admin Utama
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Pemilik Laundry',
                'email' => 'admin@raplaundry.local',
                'password' => Hash::make('admin123'),
                'role' => UserRole::Admin,
                'is_active' => true,
            ]
        );

        // Akun Kasir Operasional
        User::updateOrCreate(
            ['username' => 'kasir'],
            [
                'name' => 'Kasir Operasional',
                'email' => 'kasir@raplaundry.local',
                'password' => Hash::make('kasir123'),
                'role' => UserRole::Kasir,
                'is_active' => true,
            ]
        );
    }
}
