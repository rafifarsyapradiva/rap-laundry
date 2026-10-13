@extends('layouts.app')

@section('title', 'Manajemen Pengguna & Staf')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6);">
    <!-- Header Halaman -->
    <div class="glass-card" style="padding: var(--space-6);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-4);">
            <div>
                <h2 style="font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-main); margin: 0;">
                    Daftar Pengguna dan Staf Operasional
                </h2>
                <p style="color: var(--text-muted); font-size: var(--text-sm); margin: var(--space-1) 0 0 0;">
                    Manajemen Pengguna dan Hak Akses — Kelola akun Administrator (Pemilik) dan Kasir operasional sistem laundry.
                </p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                <x-icon name="plus" size="16" />
                <span>Tambah Pengguna Baru</span>
            </a>
        </div>
    </div>

    <!-- Filter & Pencarian Pengguna -->
    <div class="glass-card" style="padding: var(--space-5);">
        <form method="GET" action="{{ route('admin.users.index') }}" style="display: flex; flex-direction: column; gap: var(--space-4);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-4);">
                <!-- Filter Peran -->
                <div class="form-group" style="margin: 0;">
                    <label for="role" class="form-label" style="font-size: var(--text-xs);">Peran (Role)</label>
                    <select name="role" id="role" class="form-control">
                        <option value="">-- Semua Peran --</option>
                        @foreach ($roles as $r)
                            <option value="{{ $r->value }}" {{ request('role') === $r->value ? 'selected' : '' }}>
                                {{ $r->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Status -->
                <div class="form-group" style="margin: 0;">
                    <label for="is_active" class="form-label" style="font-size: var(--text-xs);">Status Akun</label>
                    <select name="is_active" id="is_active" class="form-control">
                        <option value="">-- Semua Status --</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <!-- Pencarian -->
                <div class="form-group" style="margin: 0;">
                    <label for="search" class="form-label" style="font-size: var(--text-xs);">Pencarian Nama / Akun</label>
                    <input
                        type="text"
                        name="search"
                        id="search"
                        class="form-control"
                        placeholder="Nama, username, email..."
                        value="{{ request('search') }}"
                    >
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: var(--space-2); align-items: center; border-top: 1px solid var(--border-light); padding-top: var(--space-3);">
                @if(request()->hasAny(['role', 'is_active', 'search']))
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-sm">
                        Reset Filter
                    </a>
                @endif
                <button type="submit" class="btn btn-primary btn-sm">
                    <x-icon name="search" size="14" />
                    <span>Terapkan Filter</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Tabel Pengguna -->
    <x-table :headers="['Pengguna / Nama', 'Username', 'Peran', 'Status', 'Terdaftar', 'Aksi']">
        @forelse ($users as $user)
            <tr>
                <!-- Nama & Email -->
                <td>
                    <div style="display: flex; align-items: center; gap: var(--space-2);">
                        <strong style="color: var(--text-main);">{{ $user->name }}</strong>
                        @if ($user->id === auth()->id())
                            <span class="badge badge-info" style="font-size: 10px;">Akun Anda</span>
                        @endif
                    </div>
                    <div style="font-size: var(--text-xs); color: var(--text-muted);">
                        {{ $user->email }}
                    </div>
                </td>

                <!-- Username -->
                <td>
                    <code style="font-size: var(--text-xs); font-weight: 700; color: var(--color-primary);">
                        {{ $user->username }}
                    </code>
                </td>

                <!-- Peran -->
                <td>
                    <span class="badge {{ $user->isAdmin() ? 'badge-primary' : 'badge-info' }}">
                        {{ $user->role->label() }}
                    </span>
                </td>

                <!-- Status -->
                <td>
                    <span class="badge {{ $user->is_active ? 'badge-success' : 'badge-danger' }}">
                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>

                <!-- Tanggal Terdaftar -->
                <td style="font-size: var(--text-xs); color: var(--text-muted); white-space: nowrap;">
                    {{ $user->created_at->format('d-m-Y H:i') }} WIB
                </td>

                <!-- Aksi -->
                <td>
                    <div style="display: flex; gap: var(--space-2); align-items: center; flex-wrap: wrap;">
                        <!-- Tombol Edit Profil -->
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-secondary btn-sm" title="Edit profil pengguna">
                            <x-icon name="edit" size="14" />
                            <span>Edit</span>
                        </a>

                        <!-- Tombol Reset Password -->
                        <button
                            type="button"
                            class="btn btn-secondary btn-sm"
                            title="Reset kata sandi pengguna"
                            onclick="openResetPasswordModal('{{ $user->id }}', '{{ addslashes($user->name) }}', '{{ addslashes($user->username) }}')"
                        >
                            <x-icon name="key" size="14" />
                            <span>Reset Sandi</span>
                        </button>

                        <!-- Tombol Toggle Status Aktif / Nonaktif -->
                        @if ($user->id !== auth()->id())
                            <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('PATCH')
                                <button
                                    type="submit"
                                    class="btn btn-sm {{ $user->is_active ? 'btn-danger' : 'btn-success' }}"
                                    title="{{ $user->is_active ? 'Nonaktifkan akun pengguna ini' : 'Aktifkan kembali akun ini' }}"
                                    onclick="return confirm('Apakah Anda yakin ingin {{ $user->is_active ? 'menonaktifkan' : 'mengaktifkan kembali' }} akun pengguna {{ addslashes($user->name) }}?');"
                                >
                                    <span>{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: var(--space-8);">
                    <x-empty-state
                        title="Tidak Ada Data Pengguna"
                        description="Tidak ditemukan akun staf yang sesuai dengan filter pencarian."
                        icon="users"
                    />
                </td>
            </tr>
        @endforelse
    </x-table>

    <!-- Pagination -->
    <x-pagination :paginator="$users" />
</div>

<!-- Modal Dialog Reset Kata Sandi -->
<div id="reset-password-modal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; padding: var(--space-4);">
    <div class="glass-card" style="max-width: 440px; width: 100%; padding: var(--space-6); background: #ffffff; box-shadow: var(--shadow-xl);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-4); border-bottom: 1px solid var(--border-light); padding-bottom: var(--space-3);">
            <div>
                <h3 style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin: 0;">
                    Reset Kata Sandi Pengguna
                </h3>
                <p id="reset-modal-subtitle" style="font-size: var(--text-xs); color: var(--text-muted); margin: 2px 0 0 0;">
                    Setel kata sandi baru untuk staf.
                </p>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeResetPasswordModal()" style="padding: 4px 8px;">✕</button>
        </div>

        <form id="reset-password-form" method="POST" action="">
            @csrf
            @method('PATCH')

            <div style="display: flex; flex-direction: column; gap: var(--space-4);">
                <div class="form-group" style="margin: 0;">
                    <label for="modal_password" class="form-label required">Kata Sandi Baru</label>
                    <input
                        type="password"
                        name="password"
                        id="modal_password"
                        class="form-control"
                        required
                        minlength="8"
                        placeholder="Minimal 8 karakter..."
                    >
                </div>

                <div class="form-group" style="margin: 0;">
                    <label for="modal_password_confirmation" class="form-label required">Konfirmasi Kata Sandi Baru</label>
                    <input
                        type="password"
                        name="password_confirmation"
                        id="modal_password_confirmation"
                        class="form-control"
                        required
                        minlength="8"
                        placeholder="Ulangi kata sandi baru..."
                    >
                </div>

                <div style="display: flex; justify-content: flex-end; gap: var(--space-2); margin-top: var(--space-2);">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="closeResetPasswordModal()">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <x-icon name="check" size="14" />
                        <span>Simpan Kata Sandi Baru</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openResetPasswordModal(userId, name, username) {
        var modal = document.getElementById('reset-password-modal');
        var form = document.getElementById('reset-password-form');
        var subtitle = document.getElementById('reset-modal-subtitle');

        form.action = "{{ url('admin/users') }}/" + userId + "/reset-password";
        subtitle.textContent = "Pengguna: " + name + " (@" + username + ")";
        modal.style.display = 'flex';
        document.getElementById('modal_password').focus();
    }

    function closeResetPasswordModal() {
        var modal = document.getElementById('reset-password-modal');
        modal.style.display = 'none';
        document.getElementById('reset-password-form').reset();
    }
</script>
@endpush
@endsection
