@extends('layouts.app')

@section('title', 'Pengaturan Usaha & Nota')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6);">
    <div class="glass-card" style="padding: var(--space-6);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-4);">
            <div>
                <h2 style="font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-main);">
                    Konfigurasi Usaha dan Cetak Nota
                </h2>
                <p style="color: var(--text-muted); font-size: var(--text-sm); margin-top: var(--space-1);">
                    Nama laundry, alamat, nomor kontak, dan catatan syarat pengambilan nota.
                </p>
            </div>
            <span class="badge badge-primary">Khusus Administrator</span>
        </div>
    </div>

    <div class="glass-card" style="padding: var(--space-6);">
        <x-empty-state
            title="Pengaturan Profil Usaha"
            description="Konfigurasi identitas laundry dan parameter operasional kasir dapat disesuaikan pada panel ini."
            icon="settings"
        />
    </div>
</div>
@endsection
