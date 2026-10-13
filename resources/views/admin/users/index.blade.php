@extends('layouts.app')

@section('title', 'Manajemen Pengguna & Staf')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6);">
    <div class="glass-card" style="padding: var(--space-6);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-4);">
            <div>
                <h2 style="font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-main);">
                    Daftar Pengguna dan Staf Operasional
                </h2>
                <p style="color: var(--text-muted); font-size: var(--text-sm); margin-top: var(--space-1);">
                    Kelola akun Administrator (Pemilik) dan Kasir operasional sistem laundry.
                </p>
            </div>
            <span class="badge badge-primary">Khusus Administrator</span>
        </div>
    </div>

    <div class="glass-card" style="padding: var(--space-6);">
        <x-empty-state
            title="Daftar Pengguna Terproteksi"
            description="Manajemen akun staf operasional dan hak akses dapat dikelola secara terpusat oleh pemilik sistem."
            icon="users"
        />
    </div>
</div>
@endsection
