@extends('layouts.app')

@section('title', 'Ubah Layanan — ' . $service->name)

@section('content')
<div style="max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);">
    <!-- Header Navigasi -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: var(--space-3);">
        <a href="{{ route('services.show', $service->id) }}" class="btn btn-secondary btn-sm">
            <x-icon name="arrow-left" size="14" />
            <span>Kembali ke Detail Layanan</span>
        </a>
        <span class="badge badge-primary">ID Layanan: #{{ $service->id }}</span>
    </div>

    <!-- Peringatan Integritas Snapshot Keuangan -->
    <div style="background-color: var(--primary-subtle); border: 1px solid var(--primary-light); border-radius: var(--radius-md); padding: var(--space-4); display: flex; align-items: flex-start; gap: var(--space-3);">
        <div style="color: var(--primary); margin-top: 2px;">
            <x-icon name="info" size="20" />
        </div>
        <div style="font-size: var(--text-xs); color: var(--text-main); line-height: 1.5;">
            <strong>Proteksi Snapshot Transaksi Historis:</strong>
            Perubahan harga pada formulir ini hanya berlaku untuk pesanan baru di masa mendatang. Seluruh nota transaksi yang telah dibuat sebelumnya tersimpan dalam bentuk snapshot dan <strong>tidak akan terpengaruh</strong> oleh perubahan harga master saat ini.
        </div>
    </div>

    <!-- Formulir Ubah Layanan -->
    <div class="glass-card" style="padding: var(--space-8);">
        <div style="margin-bottom: var(--space-6); border-bottom: 1px solid var(--border-subtle); padding-bottom: var(--space-4);">
            <h2 style="font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-main); display: flex; align-items: center; gap: var(--space-2);">
                <x-icon name="edit" size="20" class="icon-primary" />
                <span>Ubah Data Layanan & Tarif</span>
            </h2>
            <p style="color: var(--text-muted); font-size: var(--text-sm); margin-top: var(--space-1);">
                Perbarui nama, spesifikasi satuan, durasi pengerjaan, atau sesuaikan tarif harga resmi.
            </p>
        </div>

        <form action="{{ route('services.update', $service->id) }}" method="POST" data-prevent-double-submit>
            @csrf
            @method('PUT')

            <!-- Nama Layanan (Wajib) -->
            <x-form.input
                name="name"
                label="Nama Layanan"
                :value="$service->name"
                placeholder="Nama jenis layanan"
                :required="true"
            />

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: var(--space-4);">
                <!-- Satuan Penagihan (Wajib) -->
                <x-form.select
                    name="billing_unit"
                    label="Satuan Penagihan"
                    :selected="$service->billing_unit?->value"
                    :required="true"
                    :options="[
                        'kg' => 'Kiloan (Kg) — Mendukung berat pecahan desimal',
                        'item' => 'Per Satuan (Item) — Kuantitas helai / biji utuh',
                        'package' => 'Paket Khusus — Tarif flat / kilat',
                    ]"
                />

                <!-- Harga Tarif (Wajib) -->
                <x-form.input
                    name="price"
                    label="Tarif / Harga Satuan (Rp)"
                    :value="(float) $service->price"
                    type="number"
                    step="100"
                    min="0"
                    :required="true"
                    hint="Jika diubah, sistem akan merekam log audit perubahan harga."
                />
            </div>

            <!-- Estimasi Durasi Pengerjaan -->
            <x-form.input
                name="estimated_hours"
                label="Estimasi Durasi Pengerjaan (Dalam Jam)"
                :value="$service->estimated_hours"
                type="number"
                min="1"
                max="720"
                hint="Durasi standar pengerjaan cucian dalam jam."
            />

            <!-- Deskripsi Layanan (Opsional) -->
            <x-form.textarea
                name="description"
                label="Deskripsi & Cakupan Pengerjaan (Opsional)"
                :value="$service->description"
                placeholder="Deskripsi layanan..."
                rows="3"
            />

            <!-- Status Keaktifan -->
            <div style="margin-top: var(--space-4); margin-bottom: var(--space-6); padding: var(--space-4); background-color: var(--bg-surface); border-radius: var(--radius-md); border: 1px solid var(--border-subtle); display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <label for="is_active" style="font-weight: var(--font-semibold); font-size: var(--text-sm); color: var(--text-main); display: block; cursor: pointer;">
                        Status Layanan Aktif
                    </label>
                    <span style="font-size: var(--text-xs); color: var(--text-muted);">
                        Jika dinonaktifkan, kasir tidak dapat memilih layanan ini saat membuat transaksi baru.
                    </span>
                </div>
                <input
                    type="checkbox"
                    name="is_active"
                    id="is_active"
                    value="1"
                    {{ old('is_active', $service->is_active) ? 'checked' : '' }}
                    style="width: 1.25rem; height: 1.25rem; accent-color: var(--primary); cursor: pointer;"
                >
            </div>

            <!-- Tombol Aksi -->
            <div style="display: flex; justify-content: flex-end; gap: var(--space-3); border-top: 1px solid var(--border-subtle); padding-top: var(--space-6);">
                <a href="{{ route('services.show', $service->id) }}" class="btn btn-secondary">
                    <span>Batal</span>
                </a>
                <button type="submit" class="btn btn-primary">
                    <x-icon name="check" size="16" />
                    <span>Simpan Perubahan Tarif</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
