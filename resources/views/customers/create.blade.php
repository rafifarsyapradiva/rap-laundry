@extends('layouts.app')

@section('title', 'Tambah Pelanggan Baru')

@section('content')
<div style="max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);">
    <!-- Breadcrumb & Header Navigasi -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: var(--space-3);">
        <a href="{{ route('customers.index') }}" class="btn btn-secondary btn-sm">
            <x-icon name="arrow-left" size="14" />
            <span>Kembali ke Daftar Pelanggan</span>
        </a>
        <span class="badge badge-info">Pembuatan Kode Otomatis: CUST-XXXX</span>
    </div>

    <!-- Form Kartu Glassmorphism -->
    <div class="glass-card" style="padding: var(--space-8);">
        <div style="margin-bottom: var(--space-6); border-bottom: 1px solid var(--border-subtle); padding-bottom: var(--space-4);">
            <h2 style="font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-main); display: flex; align-items: center; gap: var(--space-2);">
                <x-icon name="plus" size="20" class="icon-primary" />
                <span>Formulir Tambah Pelanggan Baru</span>
            </h2>
            <p style="color: var(--text-muted); font-size: var(--text-sm); margin-top: var(--space-1);">
                Isi data pelanggan dengan lengkap. Kode pelanggan unik akan digenerate otomatis oleh sistem secara aman.
            </p>
        </div>

        <form action="{{ route('customers.store') }}" method="POST" data-prevent-double-submit>
            @csrf

            <!-- Kode Pelanggan Otomatis (Info Card) -->
            <div style="background-color: var(--primary-subtle); border: 1px dashed var(--primary-light); border-radius: var(--radius-md); padding: var(--space-4); margin-bottom: var(--space-6); display: flex; align-items: center; gap: var(--space-3);">
                <x-icon name="info" size="20" class="icon-primary" />
                <div style="font-size: var(--text-xs); color: var(--text-main);">
                    <strong>Nomor Kode Pelanggan:</strong> Sistem akan memberikan nomor urut otomatis (misal: <code>CUST-0004</code>) saat form ini disimpan dengan proteksi anti-duplikasi konkurensi.
                </div>
            </div>

            <!-- Nama Pelanggan (Wajib) -->
            <x-form.input
                name="name"
                label="Nama Lengkap Pelanggan"
                placeholder="Masukkan nama lengkap pelanggan (misal: Budi Santoso)"
                :required="true"
                hint="Nama minimal terdiri dari 3 karakter."
            />

            <!-- Nomor Telepon (Wajib) -->
            <x-form.input
                name="phone"
                label="Nomor Telepon / WhatsApp"
                placeholder="081234567890 atau +628123456789"
                :required="true"
                hint="Gunakan nomor aktif untuk kemudahan komunikasi dan konfirmasi pengambilan cucian."
            />

            <!-- Alamat Domisili (Opsional) -->
            <x-form.textarea
                name="address"
                label="Alamat Tempat Tinggal / Domisili (Opsional)"
                placeholder="Masukkan alamat domisili, nomor rumah, atau patokan lokasi..."
                rows="3"
                hint="Berguna untuk layanan antar-jemput atau pencatatan domisili."
            />

            <!-- Catatan Tambahan (Opsional) -->
            <x-form.textarea
                name="notes"
                label="Catatan Khusus Pelanggan (Opsional)"
                placeholder="Contoh: Langganan mingguan, alergi pemutih, minta deterjen wangi..."
                rows="2"
                hint="Catatan ini akan tampil sebagai pengingat staf saat membuat pesanan."
            />

            <!-- Status Keaktifan -->
            <div style="margin-top: var(--space-4); margin-bottom: var(--space-6); padding: var(--space-4); background-color: var(--bg-surface); border-radius: var(--radius-md); border: 1px solid var(--border-subtle); display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <label for="is_active" style="font-weight: var(--font-semibold); font-size: var(--text-sm); color: var(--text-main); display: block; cursor: pointer;">
                        Status Pelanggan Aktif
                    </label>
                    <span style="font-size: var(--text-xs); color: var(--text-muted);">
                        Pelanggan aktif dapat langsung dipilih oleh kasir pada form pesanan cucian.
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
                <a href="{{ route('customers.index') }}" class="btn btn-secondary">
                    <span>Batal</span>
                </a>
                <button type="submit" class="btn btn-primary">
                    <x-icon name="check" size="16" />
                    <span>Simpan Data Pelanggan</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
