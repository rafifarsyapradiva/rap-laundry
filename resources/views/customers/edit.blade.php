@extends('layouts.app')

@section('title', 'Ubah Pelanggan — ' . $customer->name)

@section('content')
<div style="max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);">
    <!-- Header Navigasi -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: var(--space-3);">
        <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-secondary btn-sm">
            <x-icon name="arrow-left" size="14" />
            <span>Kembali ke Detail Pelanggan</span>
        </a>
        <span class="badge badge-primary">Kode: {{ $customer->customer_code }}</span>
    </div>

    <!-- Form Kartu Glassmorphism -->
    <div class="glass-card" style="padding: var(--space-8);">
        <div style="margin-bottom: var(--space-6); border-bottom: 1px solid var(--border-subtle); padding-bottom: var(--space-4);">
            <h2 style="font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-main); display: flex; align-items: center; gap: var(--space-2);">
                <x-icon name="edit" size="20" class="icon-primary" />
                <span>Ubah Data Pelanggan</span>
            </h2>
            <p style="color: var(--text-muted); font-size: var(--text-sm); margin-top: var(--space-1);">
                Perbarui data kontak atau catatan khusus untuk pelanggan <strong>{{ $customer->name }}</strong>.
            </p>
        </div>

        <form action="{{ route('customers.update', $customer->id) }}" method="POST" data-prevent-double-submit>
            @csrf
            @method('PUT')

            <!-- Kode Pelanggan (Readonly) -->
            <div class="form-group">
                <label class="form-label">Kode Pelanggan (Tidak Dapat Diubah)</label>
                <input
                    type="text"
                    value="{{ $customer->customer_code }}"
                    readonly
                    class="form-input"
                    style="background-color: var(--primary-subtle); font-weight: var(--font-bold); color: var(--primary-dark); cursor: not-allowed;"
                >
                <div class="form-hint">Kode unik pelanggan terikat secara permanen pada seluruh riwayat transaksi.</div>
            </div>

            <!-- Nama Pelanggan (Wajib) -->
            <x-form.input
                name="name"
                label="Nama Lengkap Pelanggan"
                :value="$customer->name"
                placeholder="Masukkan nama lengkap pelanggan"
                :required="true"
                hint="Nama minimal terdiri dari 3 karakter."
            />

            <!-- Nomor Telepon (Wajib) -->
            <x-form.input
                name="phone"
                label="Nomor Telepon / WhatsApp"
                :value="$customer->phone"
                placeholder="081234567890"
                :required="true"
                hint="Nomor telepon aktif untuk pemberitahuan status cucian."
            />

            <!-- Alamat Domisili (Opsional) -->
            <x-form.textarea
                name="address"
                label="Alamat Tempat Tinggal / Domisili (Opsional)"
                :value="$customer->address"
                placeholder="Alamat domisili pelanggan..."
                rows="3"
            />

            <!-- Catatan Khusus (Opsional) -->
            <x-form.textarea
                name="notes"
                label="Catatan Khusus Pelanggan (Opsional)"
                :value="$customer->notes"
                placeholder="Contoh: Langganan mingguan, alergi pemutih..."
                rows="2"
            />

            <!-- Status Keaktifan -->
            <div style="margin-top: var(--space-4); margin-bottom: var(--space-6); padding: var(--space-4); background-color: var(--bg-surface); border-radius: var(--radius-md); border: 1px solid var(--border-subtle); display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <label for="is_active" style="font-weight: var(--font-semibold); font-size: var(--text-sm); color: var(--text-main); display: block; cursor: pointer;">
                        Status Pelanggan Aktif
                    </label>
                    <span style="font-size: var(--text-xs); color: var(--text-muted);">
                        Jika dinonaktifkan, kasir tidak dapat memilih pelanggan ini untuk pesanan baru.
                    </span>
                </div>
                <input
                    type="checkbox"
                    name="is_active"
                    id="is_active"
                    value="1"
                    {{ old('is_active', $customer->is_active) ? 'checked' : '' }}
                    style="width: 1.25rem; height: 1.25rem; accent-color: var(--primary); cursor: pointer;"
                >
            </div>

            <!-- Tombol Aksi -->
            <div style="display: flex; justify-content: flex-end; gap: var(--space-3); border-top: 1px solid var(--border-subtle); padding-top: var(--space-6);">
                <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-secondary">
                    <span>Batal</span>
                </a>
                <button type="submit" class="btn btn-primary">
                    <x-icon name="check" size="16" />
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
