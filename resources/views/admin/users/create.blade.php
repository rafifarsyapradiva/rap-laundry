@extends('layouts.app')

@section('title', 'Tambah Pengguna Baru')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6); max-width: 800px; margin: 0 auto;">
    <!-- Header Halaman -->
    <div class="glass-card" style="padding: var(--space-6);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-4);">
            <div>
                <h2 style="font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-main); margin: 0;">
                    Tambah Pengguna & Staf Baru
                </h2>
                <p style="color: var(--text-muted); font-size: var(--text-sm); margin: var(--space-1) 0 0 0;">
                    Daftarkan akun staf baru untuk mengoperasikan kasir atau mengelola sistem.
                </p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-sm">
                <span>&larr; Kembali ke Daftar</span>
            </a>
        </div>
    </div>

    <!-- Formulir Pendaftaran Pengguna -->
    <div class="glass-card" style="padding: var(--space-6);">
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf

            <div style="display: flex; flex-direction: column; gap: var(--space-4);">
                <!-- Nama Lengkap -->
                <div class="form-group">
                    <label for="name" class="form-label required">Nama Lengkap</label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}"
                        required
                        maxlength="100"
                        placeholder="Contoh: Budi Santoso"
                    >
                    @error('name')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Username & Email -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: var(--space-4);">
                    <div class="form-group">
                        <label for="username" class="form-label required">Username Akun</label>
                        <input
                            type="text"
                            name="username"
                            id="username"
                            class="form-control @error('username') is-invalid @enderror"
                            value="{{ old('username') }}"
                            required
                            minlength="3"
                            maxlength="50"
                            placeholder="Contoh: budi_kasir"
                        >
                        <span class="form-text">Digunakan untuk login (huruf kecil, angka, _, -).</span>
                        @error('username')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label required">Alamat Email</label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}"
                            required
                            maxlength="100"
                            placeholder="Contoh: budi@raplaundry.local"
                        >
                        @error('email')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Password & Konfirmasi -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: var(--space-4);">
                    <div class="form-group">
                        <label for="password" class="form-label required">Kata Sandi</label>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control @error('password') is-invalid @enderror"
                            required
                            minlength="8"
                            placeholder="Minimal 8 karakter..."
                        >
                        @error('password')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation" class="form-label required">Konfirmasi Kata Sandi</label>
                        <input
                            type="password"
                            name="password_confirmation"
                            id="password_confirmation"
                            class="form-control"
                            required
                            minlength="8"
                            placeholder="Ulangi kata sandi..."
                        >
                    </div>
                </div>

                <!-- Peran & Status Aktif -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: var(--space-4);">
                    <div class="form-group">
                        <label for="role" class="form-label required">Peran Pengguna (Role)</label>
                        <select name="role" id="role" class="form-control @error('role') is-invalid @enderror" required>
                            @foreach ($roles as $r)
                                <option value="{{ $r->value }}" {{ old('role', \App\Enums\UserRole::Kasir->value) === $r->value ? 'selected' : '' }}>
                                    {{ $r->label() }} — {{ $r === \App\Enums\UserRole::Admin ? 'Akses penuh ke seluruh menu & pengaturan' : 'Akses operasional kasir & pesanan' }}
                                </option>
                            @endforeach
                        </select>
                        @error('role')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Status Awal Akun</label>
                        <div style="display: flex; gap: var(--space-4); margin-top: var(--space-2);">
                            <label style="display: inline-flex; align-items: center; gap: var(--space-2); cursor: pointer;">
                                <input type="radio" name="is_active" value="1" {{ old('is_active', '1') === '1' ? 'checked' : '' }}>
                                <span><strong>Aktif</strong> (Bisa langsung login)</span>
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: var(--space-2); cursor: pointer;">
                                <input type="radio" name="is_active" value="0" {{ old('is_active') === '0' ? 'checked' : '' }}>
                                <span><strong>Nonaktif</strong> (Ditangguhkan)</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi -->
                <div style="display: flex; justify-content: flex-end; gap: var(--space-3); margin-top: var(--space-4); border-top: 1px solid var(--border-light); padding-top: var(--space-4);">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" size="16" />
                        <span>Daftarkan Pengguna</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
