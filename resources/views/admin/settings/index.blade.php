@extends('layouts.app')

@section('title', 'Pengaturan Usaha & Nota')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6);">
    <!-- Header Banner Halaman -->
    <div class="glass-card" style="padding: var(--space-6);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-4);">
            <div>
                <h2 style="font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-main); margin: 0;">
                    Konfigurasi Usaha dan Cetak Nota
                </h2>
                <p style="color: var(--text-muted); font-size: var(--text-sm); margin: var(--space-1) 0 0 0;">
                    Kelola profil laundry, format penomoran pesanan, parameter kasir, dan template nota fisik.
                </p>
            </div>
            <span class="badge badge-primary">Khusus Administrator</span>
        </div>
    </div>

    <!-- Formulir Pengaturan Usaha -->
    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" id="settings-form">
        @csrf
        @method('PUT')

        <div style="display: flex; flex-direction: column; gap: var(--space-6);">
            <!-- Bagian 1: Identitas & Kontak Usaha -->
            <div class="glass-card" style="padding: var(--space-6);">
                <div style="display: flex; align-items: center; gap: var(--space-3); margin-bottom: var(--space-5); border-bottom: 1px solid var(--border-light); padding-bottom: var(--space-3);">
                    <x-icon name="building" size="20" />
                    <div>
                        <h3 style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin: 0;">
                            Identitas dan Kontak Laundry
                        </h3>
                        <p style="font-size: var(--text-xs); color: var(--text-muted); margin: 2px 0 0 0;">
                            Informasi kop usaha yang dicetak pada faktur A4 dan struk thermal kasir.
                        </p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--space-4);">
                    <div class="form-group">
                        <label for="business_name" class="form-label required">Nama Usaha Laundry</label>
                        <input
                            type="text"
                            name="business_name"
                            id="business_name"
                            class="form-control @error('business_name') is-invalid @enderror"
                            value="{{ old('business_name', $settings[\App\Models\BusinessSetting::KEY_BUSINESS_NAME] ?? 'RAP Laundry') }}"
                            required
                            maxlength="100"
                            placeholder="Contoh: RAP Laundry"
                        >
                        @error('business_name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="business_phone" class="form-label required">Nomor Telepon / WhatsApp</label>
                        <input
                            type="text"
                            name="business_phone"
                            id="business_phone"
                            class="form-control @error('business_phone') is-invalid @enderror"
                            value="{{ old('business_phone', $settings[\App\Models\BusinessSetting::KEY_BUSINESS_PHONE] ?? '') }}"
                            required
                            maxlength="20"
                            placeholder="Contoh: 081234567890"
                        >
                        @error('business_phone')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="business_address" class="form-label required">Alamat Lengkap Usaha</label>
                        <input
                            type="text"
                            name="business_address"
                            id="business_address"
                            class="form-control @error('business_address') is-invalid @enderror"
                            value="{{ old('business_address', $settings[\App\Models\BusinessSetting::KEY_BUSINESS_ADDRESS] ?? '') }}"
                            required
                            maxlength="255"
                            placeholder="Contoh: Jl. Kebon Jeruk No. 12, Jakarta Barat"
                        >
                        @error('business_address')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="operating_hours" class="form-label">Jam Operasional Laundry</label>
                        <input
                            type="text"
                            name="operating_hours"
                            id="operating_hours"
                            class="form-control @error('operating_hours') is-invalid @enderror"
                            value="{{ old('operating_hours', $settings[\App\Models\BusinessSetting::KEY_OPERATING_HOURS] ?? '') }}"
                            maxlength="100"
                            placeholder="Contoh: Senin - Sabtu: 08.00 - 20.00 WIB"
                        >
                        <span class="form-text">Ditampilkan pada catatan kop nota fisik kasir.</span>
                        @error('operating_hours')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Bagian 2: Logo Usaha (Lokal Offline) -->
            <div class="glass-card" style="padding: var(--space-6);">
                <div style="display: flex; align-items: center; gap: var(--space-3); margin-bottom: var(--space-5); border-bottom: 1px solid var(--border-light); padding-bottom: var(--space-3);">
                    <x-icon name="image" size="20" />
                    <div>
                        <h3 style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin: 0;">
                            Logo Usaha Laundry (Opsional)
                        </h3>
                        <p style="font-size: var(--text-xs); color: var(--text-muted); margin: 2px 0 0 0;">
                            Logo disimpan secara mandiri di media penyimpanan lokal tanpa dependensi internet.
                        </p>
                    </div>
                </div>

                <div style="display: flex; gap: var(--space-6); align-items: flex-start; flex-wrap: wrap;">
                    <!-- Area Pratinjau Logo -->
                    <div style="width: 160px; height: 110px; border: 2px dashed var(--border-color); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; background-color: rgba(255, 255, 255, 0.4); padding: var(--space-2); text-align: center; flex-shrink: 0;">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="Logo Usaha" style="max-height: 90px; max-width: 140px; object-fit: contain; border-radius: 4px;" id="logo-preview">
                        @else
                            <div id="no-logo-placeholder" style="color: var(--text-muted); font-size: var(--text-xs);">
                                <x-icon name="image" size="28" style="opacity: 0.5; margin-bottom: 4px;" />
                                <div>Belum Ada Logo</div>
                            </div>
                        @endif
                    </div>

                    <!-- Input Berkas & Opsi Hapus -->
                    <div style="flex: 1; min-width: 260px;">
                        <div class="form-group">
                            <label for="logo" class="form-label">Unggah Berkas Logo Baru</label>
                            <input
                                type="file"
                                name="logo"
                                id="logo"
                                class="form-control @error('logo') is-invalid @enderror"
                                accept="image/png, image/jpeg, image/webp"
                            >
                            <span class="form-text">
                                Format didukung: <strong>PNG, JPG, WEBP</strong>. Ukuran maksimum: <strong>1.024 KB (1 MB)</strong>.
                            </span>
                            @error('logo')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>

                        @if ($logoUrl)
                            <div style="margin-top: var(--space-3);">
                                <label style="display: inline-flex; align-items: center; gap: var(--space-2); font-size: var(--text-sm); color: var(--color-danger); cursor: pointer;">
                                    <input type="checkbox" name="remove_logo" value="1" {{ old('remove_logo') ? 'checked' : '' }}>
                                    <span>Hapus logo saat ini dan gunakan teks nama usaha standar</span>
                                </label>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Bagian 3: Parameter Transaksi & Kasir -->
            <div class="glass-card" style="padding: var(--space-6);">
                <div style="display: flex; align-items: center; gap: var(--space-3); margin-bottom: var(--space-5); border-bottom: 1px solid var(--border-light); padding-bottom: var(--space-3);">
                    <x-icon name="sliders" size="20" />
                    <div>
                        <h3 style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin: 0;">
                            Parameter Transaksi & Operasional Kasir
                        </h3>
                        <p style="font-size: var(--text-xs); color: var(--text-muted); margin: 2px 0 0 0;">
                            Konfigurasi nomor pesanan, estimasi pengerjaan, dan plafon diskon kasir.
                        </p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: var(--space-4);">
                    <div class="form-group">
                        <label for="order_number_prefix" class="form-label required">Awalan Nomor Pesanan (Prefix)</label>
                        <input
                            type="text"
                            name="order_number_prefix"
                            id="order_number_prefix"
                            class="form-control @error('order_number_prefix') is-invalid @enderror"
                            value="{{ old('order_number_prefix', $settings[\App\Models\BusinessSetting::KEY_ORDER_NUMBER_PREFIX] ?? 'LD') }}"
                            required
                            minlength="2"
                            maxlength="5"
                            placeholder="Contoh: LD"
                            style="text-transform: uppercase; font-family: monospace; font-weight: 700;"
                        >
                        <span class="form-text">Contoh hasil: <code>LD-{{ date('Ymd') }}-0001</code>.</span>
                        @error('order_number_prefix')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="default_estimated_hours" class="form-label required">Durasi Estimasi Selesai Bawaan (Jam)</label>
                        <input
                            type="number"
                            name="default_estimated_hours"
                            id="default_estimated_hours"
                            class="form-control @error('default_estimated_hours') is-invalid @enderror"
                            value="{{ old('default_estimated_hours', $settings[\App\Models\BusinessSetting::KEY_DEFAULT_ESTIMATED_HOURS] ?? '48') }}"
                            required
                            min="1"
                            max="720"
                            placeholder="48"
                        >
                        <span class="form-text">Contoh: <code>24</code> = 1 hari, <code>48</code> = 2 hari.</span>
                        @error('default_estimated_hours')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="cashier_max_discount_percent" class="form-label required">Batas Diskon Staf Kasir (%)</label>
                        <input
                            type="number"
                            name="cashier_max_discount_percent"
                            id="cashier_max_discount_percent"
                            class="form-control @error('cashier_max_discount_percent') is-invalid @enderror"
                            value="{{ old('cashier_max_discount_percent', $settings[\App\Models\BusinessSetting::KEY_CASHIER_MAX_DISCOUNT_PERCENT] ?? '20') }}"
                            required
                            min="0"
                            max="100"
                            placeholder="20"
                        >
                        <span class="form-text">Admin tetap berhak memberikan diskon hingga 100%.</span>
                        @error('cashier_max_discount_percent')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Bagian 4: Format Cetak & Catatan Kaki Nota -->
            <div class="glass-card" style="padding: var(--space-6);">
                <div style="display: flex; align-items: center; gap: var(--space-3); margin-bottom: var(--space-5); border-bottom: 1px solid var(--border-light); padding-bottom: var(--space-3);">
                    <x-icon name="printer" size="20" />
                    <div>
                        <h3 style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin: 0;">
                            Preferensi Cetak Nota Fisik
                        </h3>
                        <p style="font-size: var(--text-xs); color: var(--text-muted); margin: 2px 0 0 0;">
                            Pilihan format cetak default dan catatan syarat pengambilan bagi pelanggan.
                        </p>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: var(--space-4);">
                    <div class="form-group">
                        <label class="form-label required">Format Cetak Bawaan</label>
                        <div style="display: flex; gap: var(--space-6); flex-wrap: wrap; margin-top: var(--space-1);">
                            @php
                                $currentFormat = old('print_format', $settings[\App\Models\BusinessSetting::KEY_PRINT_FORMAT] ?? 'a4');
                            @endphp
                            <label style="display: inline-flex; align-items: center; gap: var(--space-2); cursor: pointer;">
                                <input type="radio" name="print_format" value="a4" {{ $currentFormat === 'a4' ? 'checked' : '' }} required>
                                <span><strong>Faktur Standar (A4)</strong> — Lengkap dengan tabel detail dan kolom tanda tangan</span>
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: var(--space-2); cursor: pointer;">
                                <input type="radio" name="print_format" value="thermal" {{ $currentFormat === 'thermal' ? 'checked' : '' }}>
                                <span><strong>Struk Thermal (58/80mm)</strong> — Ringkas, hemat kertas, cocok untuk printer kasir mini</span>
                            </label>
                        </div>
                        @error('print_format')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="receipt_footer_note" class="form-label">Catatan Syarat Pengambilan pada Nota (Footer Note)</label>
                        <textarea
                            name="receipt_footer_note"
                            id="receipt_footer_note"
                            rows="3"
                            class="form-control @error('receipt_footer_note') is-invalid @enderror"
                            maxlength="500"
                            placeholder="Contoh: Harap bawa nota ini saat pengambilan pakaian. Pakaian yang tidak diambil dalam 30 hari di luar tanggung jawab laundry."
                        >{{ old('receipt_footer_note', $settings[\App\Models\BusinessSetting::KEY_RECEIPT_FOOTER_NOTE] ?? '') }}</textarea>
                        <span class="form-text">Tercetak di bagian bawah nota pelanggan. Maksimal 500 karakter.</span>
                        @error('receipt_footer_note')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Tombol Aksi Simpan -->
            <div style="display: flex; justify-content: flex-end; gap: var(--space-3); align-items: center;">
                <button type="submit" class="btn btn-primary" id="save-settings-btn" style="padding: var(--space-3) var(--space-6); font-size: var(--text-base);">
                    <x-icon name="check" size="18" />
                    <span>Simpan Perubahan Pengaturan</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
