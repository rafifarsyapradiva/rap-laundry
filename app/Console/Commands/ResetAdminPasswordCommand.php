<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetAdminPasswordCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laundry:reset-admin 
                            {--username=admin : Nama pengguna akun administrator} 
                            {--password= : Kata sandi baru (minimal 8 karakter)} 
                            {--name= : Nama lengkap administrator}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setel ulang atau buat akun Administrator utama secara offline dengan aman';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $username = (string) ($this->option('username') ?: 'admin');
        $password = (string) $this->option('password');
        $name = (string) $this->option('name');

        // Jika password tidak disediakan via opsi, minta secara interaktif
        if (blank($password)) {
            $password = (string) $this->secret('Masukkan kata sandi baru untuk akun Admin (minimal 8 karakter):');
        }

        if (strlen($password) < 8) {
            $this->error('Kata sandi harus memiliki panjang minimal 8 karakter demi keamanan sistem.');
            return self::FAILURE;
        }

        $user = User::firstOrNew(['username' => $username]);

        $user->name = filled($name) ? $name : ($user->name ?? 'Pemilik Laundry');
        $user->email = $user->email ?? "{$username}@raplaundry.local";
        $user->password = Hash::make($password);
        $user->role = UserRole::Admin;
        $user->is_active = true;
        $user->save();

        $this->info("Akun Admin [{$user->username}] berhasil disetel ulang dengan peran Administrator.");
        $this->line("Nama: {$user->name}");
        $this->line("Email: {$user->email}");
        $this->line("Status: Aktif");

        return self::SUCCESS;
    }
}
