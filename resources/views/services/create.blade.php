@extends('layouts.app')

@section('title', 'Tambah Layanan Baru')

@section('content')
<div style="max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);">
    <!-- Header Navigasi -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: var(--space-3);">
        <a href="{{ route('services.index') }}" class="btn btn-secondary btn-sm">
            <x-icon name="arrow-left" size="14" />
            <span>Kembali ke Master Layanan</span>
        </a>
        <span class="badge badge-primary">Khusus Administrator / Pemilik</span>
    </div>

    <!-- Formulir Pendaftaran Layanan -->
    <div class="glass-card" style="padding: var(--space-8);">
        <div style="margin-bottom: var(--space-6); border-bottom: 1px solid var(--border-subtle); padding-bottom: var(--space-4);">
            <h2 style="font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-main); display: flex; align-items: center; gap: var(--space-2);">
                <x-icon name="plus" size="20" class="icon-primary" />
                <span>Formulir Tambah Jenis Layanan Baru</span>
            </h2>
            <p style="color: var(--text-muted); font-size: var(--text-sm); margin-top: var(--space-1);">
                Tentukan nama layanan, satuan penagihan, estimasi pengerjaan, dan tarif harga resmi.
            </p>
        </div>

        <form action="{{ route('services.store') }}" method="POST" data-prevent-double-submit>
            @csrf

            <!-- Nama Layanan (Wajib) -->
            <x-form.input
                name="name"
                label="Nama Layanan"
                placeholder="Contoh: Cuci Kering, Cuci Setrika, Bed Cover..."
                :required="true"
                hint="Nama jenis layanan yang tampil pada daftar kasir dan nota cetak."
            />

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: var(--space-4);">
                <!-- Satuan Penagihan (Wajib) -->
                <x-form.select
                    name="billing_unit"
                    label="Satuan Penagihan"
                    :required="true"
                    :options="[
                        'kg' => 'Kiloan (Kg) — Mendukung berat pecahan desimal',
                        'item' => 'Per Satuan (Item) — Kuantitas helai / biji utuh',
                        'package' => 'Paket Khusus — Tarif flat / kilat',
                    ]"
                    placeholder="-- Pilih Satuan Penagihan --"
                    hint="Kiloan mendukung timbangan desimal (misal 2.5 kg)."
                />

                <!-- Harga Tarif (Wajib) -->
                <x-form.input
                    name="price"
                    label="Tarif / Harga Satuan (Rp)"
                    placeholder="Contoh: 8000"
                    type="number"
                    step="100"
                    min="0"
                    :required="true"
                    hint="Tarif dasar per satuan (tanpa desimal atau tanda titik)."
                />
            </div>

            <!-- Estimasi Durasi Pengerjaan -->
            <x-form.input
                name="estimated_hours"
                label="Estimasi Durasi Pengerjaan (Dalam Jam)"
                placeholder="Contoh: 24 untuk 1 hari, 48 untuk 2 hari, 6 untuk express..."
                type="number"
                min="1"
                max="720"
                hint="Digunakan sistem untuk menghitung estimasi tanggal selesai pesanan cucian secara otomatis."
            />

            <!-- Deskripsi Layanan (Opsional) -->
            <x-form.textarea
                name="description"
                label="Deskripsi & Cakupan Pengerjaan (Opsional)"
                placeholder="Jelaskan proses layanan (misal: Pencucian pakaian, pengeringan mesin, dan setrika rapi)..."
                rows="3"
            />

            <!-- Status Keaktifan -->
            <div style="margin-top: var(--space-4); margin-bottom: var(--space-6); padding: var(--space-4); background-color: var(--bg-surface); border-radius: var(--radius-md); border: 1px solid var(--border-subtle); display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <label for="is_active" style="font-weight: var(--font-semibold); font-size: var(--text-sm); color: var(--text-main); display: block; cursor: pointer;">
                        Status Layanan Aktif
                    </label>
                    <span style="font-size: var(--text-xs); color: var(--text-muted);">
                        Layanan yang aktif dapat langsung dipilih oleh kasir saat membuat pesanan baru.
                    </span>
                </div>
                <input
                    type="checkbox"
                    name="is_active"
                    id="is_active"
                    value="1"
                    {{ old('is_active', '1') ? 'checked' : '' }}
                    style="width: 1.25rem; height: 1.25rem; accent-color: var(--primary); cursor: pointer;"
                >
            </div>

            <!-- Tombol Aksi -->
            <div style="display: flex; justify-content: flex-end; gap: var(--space-3); border-top: 1px solid var(--border-subtle); padding-top: var(--space-6);">
                <a href="{{ route('services.index') }}" class="btn btn-secondary">
                    <span>Batal</span>
                </a>
                <button type="submit" class="btn btn-primary">
                    <x-icon name="check" size="16" />
                    <span>Simpan Master Layanan</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
