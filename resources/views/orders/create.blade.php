@extends('layouts.app')

@section('title', 'Buat Pesanan Laundry Baru')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6); max-width: 1200px; margin: 0 auto;">

    <!-- Breadcrumb & Top Bar -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: var(--space-3);">
        <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm">
            <x-icon name="arrow-left" size="14" />
            <span>Kembali ke Dashboard</span>
        </a>
        <div style="display: flex; align-items: center; gap: var(--space-2);">
            <span class="badge badge-primary" style="font-size: var(--text-xs); padding: var(--space-2) var(--space-3);">
                No. Transaksi Otomatis: LD-{{ now()->format('Ymd') }}-XXXX
            </span>
            <span class="badge badge-info" style="font-size: var(--text-xs); padding: var(--space-2) var(--space-3);">
                Petugas: {{ auth()->user()->name }} ({{ auth()->user()->role->label() }})
            </span>
        </div>
    </div>

    <!-- Alert Container Lokal untuk Notifikasi AJAX -->
    <div id="order-form-alert" class="alert" style="display: none;"></div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <div style="font-weight: var(--font-bold); margin-bottom: var(--space-2); display: flex; align-items: center; gap: var(--space-2);">
                <x-icon name="alert-triangle" size="18" />
                <span>Terdapat kesalahan pengisian form pesanan:</span>
            </div>
            <ul style="margin: 0; padding-left: var(--space-5); font-size: var(--text-sm);">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('orders.store') }}" method="POST" id="main-order-form" data-prevent-double-submit>
        @csrf

        <!-- ===================================================================
             KARTU 1: DATA PELANGGAN & INFORMASI WAKTU
             =================================================================== -->
        <div class="glass-card" style="padding: var(--space-6); margin-bottom: var(--space-6);">
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-subtle); padding-bottom: var(--space-4); margin-bottom: var(--space-5); flex-wrap: wrap; gap: var(--space-3);">
                <div>
                    <h2 style="font-size: var(--text-lg); font-weight: var(--font-bold); color: var(--text-main); display: flex; align-items: center; gap: var(--space-2);">
                        <x-icon name="customers" size="20" class="icon-primary" />
                        <span>1. Informasi Pelanggan & Waktu Penerimaan</span>
                    </h2>
                    <p style="color: var(--text-muted); font-size: var(--text-xs); margin-top: var(--space-1);">
                        Pilih pelanggan terdaftar atau daftarkan pelanggan baru tanpa kehilangan baris cucian.
                    </p>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" id="btn-open-quick-customer">
                    <x-icon name="plus" size="14" />
                    <span>+ Pelanggan Baru</span>
                </button>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--space-5);">
                <!-- Select Pelanggan -->
                <div>
                    <label for="customer_id" class="form-label" style="font-weight: var(--font-semibold); display: flex; justify-content: space-between;">
                        <span>Pilih Pelanggan <span style="color: var(--status-danger);">*</span></span>
                        <span style="font-size: var(--text-xs); color: var(--text-muted);">Total Aktif: {{ $customers->count() }}</span>
                    </label>
                    <select name="customer_id" id="customer_id" class="form-control form-select" required>
                        <option value="">-- Pilih Pelanggan --</option>
                        @foreach ($customers as $cust)
                            <option value="{{ $cust->id }}"
                                    data-name="{{ $cust->name }}"
                                    data-code="{{ $cust->customer_code }}"
                                    data-phone="{{ $cust->formatted_phone }}"
                                    data-address="{{ $cust->address }}"
                                    {{ old('customer_id', $selectedCustomerId) == $cust->id ? 'selected' : '' }}>
                                {{ $cust->name }} ({{ $cust->customer_code }}) - {{ $cust->formatted_phone }}
                            </option>
                        @endforeach
                    </select>
                    <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-1);">
                        Cari pelanggan berdasarkan nama, kode pelanggan, atau nomor telepon.
                    </div>
                </div>

                <!-- Waktu Penerimaan -->
                <div>
                    <label for="received_at" class="form-label" style="font-weight: var(--font-semibold);">
                        Waktu Masuk / Diterima <span style="color: var(--status-danger);">*</span>
                    </label>
                    <input type="datetime-local"
                           name="received_at"
                           id="received_at"
                           class="form-control"
                           value="{{ old('received_at', $receivedAt->format('Y-m-d\TH:i')) }}"
                           required />
                    <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-1);">
                        Default waktu sekarang (Asia/Jakarta).
                    </div>
                </div>
            </div>

            <!-- Kartu Pratinjau Identitas Pelanggan Terpilih -->
            <div id="customer-info-card" style="display: none; margin-top: var(--space-4); background-color: var(--primary-subtle); border: 1px solid var(--primary-light); border-radius: var(--radius-md); padding: var(--space-4);">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: var(--space-2);">
                    <div>
                        <div style="font-size: var(--text-xs); text-transform: uppercase; letter-spacing: 0.05em; color: var(--primary); font-weight: var(--font-bold);">
                            Profil Pelanggan Terpilih
                        </div>
                        <div style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin-top: 2px;">
                            <span id="customer-info-name">-</span>
                            <span class="badge badge-primary" style="margin-left: var(--space-2);" id="customer-info-code">-</span>
                        </div>
                        <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1); display: flex; align-items: center; gap: var(--space-4); flex-wrap: wrap;">
                            <span><strong>No. Telepon/WA:</strong> <span id="customer-info-phone">-</span></span>
                            <span><strong>Alamat:</strong> <span id="customer-info-address">-</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================================================================
             KARTU 2: DETAIL ITEM LAYANAN (DYNAMIC REPEATER)
             =================================================================== -->
        <div class="glass-card" style="padding: var(--space-6); margin-bottom: var(--space-6);">
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-subtle); padding-bottom: var(--space-4); margin-bottom: var(--space-5); flex-wrap: wrap; gap: var(--space-3);">
                <div>
                    <h2 style="font-size: var(--text-lg); font-weight: var(--font-bold); color: var(--text-main); display: flex; align-items: center; gap: var(--space-2);">
                        <x-icon name="services" size="20" class="icon-primary" />
                        <span>2. Rincian Layanan Cucian (Multi-Item)</span>
                    </h2>
                    <p style="color: var(--text-muted); font-size: var(--text-xs); margin-top: var(--space-1);">
                        Dukung beberapa item layanan. Satuan kilogram mendukung koma desimal (misal: <code>2.5 kg</code>).
                    </p>
                </div>
                <button type="button" class="btn btn-primary btn-sm" id="btn-add-service-row">
                    <x-icon name="plus" size="14" />
                    <span>+ Tambah Layanan</span>
                </button>
            </div>

            <!-- Tabel Repeater Layanan -->
            <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table class="table-glass" style="width: 100%; min-width: 850px;" id="order-items-table">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">No</th>
                            <th style="min-width: 220px;">Layanan Master</th>
                            <th style="width: 90px; text-align: center;">Satuan</th>
                            <th style="width: 140px; text-align: right;">Kuantitas / Berat</th>
                            <th style="width: 130px; text-align: right;">Tarif Satuan</th>
                            <th style="width: 140px; text-align: right;">Subtotal</th>
                            <th style="min-width: 200px;">Catatan Khusus Pakaian</th>
                            <th style="width: 60px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="order-items-tbody">
                        <!-- Baris dirender secara dinamis oleh order-form.js -->
                    </tbody>
                </table>
            </div>

            <div style="margin-top: var(--space-4); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-3);">
                <button type="button" class="btn btn-secondary btn-sm" id="btn-add-service-row-bottom" onclick="document.getElementById('btn-add-service-row').click()">
                    <x-icon name="plus" size="14" />
                    <span>+ Tambah Baris Layanan Lain</span>
                </button>
                <div style="font-size: var(--text-xs); color: var(--text-muted);">
                    * Harga snapshot akan dikunci otomatis saat pesanan disimpan.
                </div>
            </div>
        </div>

        <!-- ===================================================================
             KARTU 3: ESTIMASI WAKTU SELESAI & CATATAN PESANAN
             =================================================================== -->
        <div class="glass-card" style="padding: var(--space-6); margin-bottom: var(--space-6);">
            <div style="border-bottom: 1px solid var(--border-subtle); padding-bottom: var(--space-4); margin-bottom: var(--space-5);">
                <h2 style="font-size: var(--text-lg); font-weight: var(--font-bold); color: var(--text-main); display: flex; align-items: center; gap: var(--space-2);">
                    <x-icon name="clock" size="20" class="icon-primary" />
                    <span>3. Estimasi Selesai & Catatan Pesanan</span>
                </h2>
                <p style="color: var(--text-muted); font-size: var(--text-xs); margin-top: var(--space-1);">
                    Estimasi otomatis dihitung mengikuti durasi pengerjaan terlama dari layanan yang dipilih.
                </p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: var(--space-6);">
                <!-- Estimasi Waktu Selesai -->
                <div>
                    <label for="estimated_completion_at" class="form-label" style="font-weight: var(--font-semibold); display: flex; justify-content: space-between; align-items: center;">
                        <span>Target Estimasi Selesai</span>
                        <span id="estimated-duration-info" class="badge badge-neutral" style="font-size: 11px;">
                            Estimasi durasi: {{ $defaultEstimatedHours }} jam
                        </span>
                    </label>
                    <input type="datetime-local"
                           name="estimated_completion_at"
                           id="estimated_completion_at"
                           class="form-control"
                           value="{{ old('estimated_completion_at', $defaultEstimatedCompletionAt->format('Y-m-d\TH:i')) }}" />

                    <!-- Quick Preset Buttons -->
                    <div style="display: flex; gap: var(--space-2); margin-top: var(--space-2); flex-wrap: wrap;">
                        <button type="button" class="btn btn-secondary btn-sm btn-preset-hours" data-hours="24">+1 Hari</button>
                        <button type="button" class="btn btn-secondary btn-sm btn-preset-hours" data-hours="48">+2 Hari</button>
                        <button type="button" class="btn btn-secondary btn-sm btn-preset-hours" data-hours="72">+3 Hari</button>
                        <button type="button" class="btn btn-secondary btn-sm" id="btn-reset-service-estimate" title="Kembalikan sesuai kalkulasi durasi master layanan">
                            Sesuai Layanan
                        </button>
                    </div>
                    <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                        Kasir dapat memilih tombol cepat atau menentukan tanggal & jam serah terima secara manual.
                    </div>
                </div>

                <!-- Catatan Khusus Pesanan -->
                <div>
                    <label for="notes" class="form-label" style="font-weight: var(--font-semibold);">
                        Catatan Khusus Pesanan (Opsional)
                    </label>
                    <textarea name="notes"
                              id="notes"
                              rows="3"
                              class="form-control"
                              placeholder="Misal: Jangan dicampur pakaian luntur, gunakan deterjen khusus bayi, lipat tanpa hanger...">{{ old('notes') }}</textarea>
                    <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-1);">
                        Catatan ini akan tercetak pada lembar nota cucian pelanggan.
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================================================================
             KARTU 4: KALKULASI FINANSIAL & DISKON SESUAI IZIN
             =================================================================== -->
        <div class="glass-card" style="padding: var(--space-6); margin-bottom: var(--space-6);">
            <div style="border-bottom: 1px solid var(--border-subtle); padding-bottom: var(--space-4); margin-bottom: var(--space-5);">
                <h2 style="font-size: var(--text-lg); font-weight: var(--font-bold); color: var(--text-main); display: flex; align-items: center; gap: var(--space-2);">
                    <x-icon name="payments" size="20" class="icon-primary" />
                    <span>4. Ringkasan Finansial & Otorisasi Biaya</span>
                </h2>
                <p style="color: var(--text-muted); font-size: var(--text-xs); margin-top: var(--space-1);">
                    Subtotal, diskon sesuai izin peran, biaya tambahan, dan total akhir tagihan.
                </p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--space-6); align-items: start;">
                <!-- Kolom Input Diskon & Biaya Tambahan -->
                <div style="display: flex; flex-direction: column; gap: var(--space-4);">
                    <!-- Diskon -->
                    <div>
                        <label for="discount_amount" class="form-label" style="font-weight: var(--font-semibold); display: flex; justify-content: space-between;">
                            <span>Potongan Diskon (Rp)</span>
                            @if(auth()->user()->isAdmin())
                                <span class="badge badge-success" style="font-size: 11px;">Admin: Izin Penuh (100%)</span>
                            @else
                                <span class="badge badge-warning" style="font-size: 11px;">
                                    Kasir: Maks. {{ $maxDiscountPercent }}% (<span id="cashier-max-discount-text">Rp 0</span>)
                                </span>
                            @endif
                        </label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: var(--space-3); top: 50%; transform: translateY(-50%); color: var(--text-muted); font-weight: var(--font-bold);">
                                Rp
                            </span>
                            <input type="text"
                                   name="discount_amount"
                                   id="discount_amount"
                                   class="form-control"
                                   style="padding-left: 45px; font-weight: var(--font-semibold);"
                                   value="{{ old('discount_amount', '0') }}"
                                   placeholder="0" />
                        </div>
                        <div id="discount-warning-msg" style="display: none; color: var(--status-danger); font-size: var(--text-xs); margin-top: var(--space-1); font-weight: var(--font-medium);"></div>
                        <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-1);">
                            Nominal diskon rupiah bulat. Tidak boleh melebihi total subtotal pesanan.
                        </div>
                    </div>

                    <!-- Biaya Tambahan -->
                    <div>
                        <label for="extra_charge" class="form-label" style="font-weight: var(--font-semibold);">
                            Biaya Tambahan (Rp)
                        </label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: var(--space-3); top: 50%; transform: translateY(-50%); color: var(--text-muted); font-weight: var(--font-bold);">
                                Rp
                            </span>
                            <input type="text"
                                   name="extra_charge"
                                   id="extra_charge"
                                   class="form-control"
                                   style="padding-left: 45px; font-weight: var(--font-semibold);"
                                   value="{{ old('extra_charge', '0') }}"
                                   placeholder="0" />
                        </div>
                        <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-1);">
                            Misal: Biaya kilat express, ongkos antar-jemput, atau pewangi premium.
                        </div>
                    </div>
                </div>

                <!-- Panel Ringkasan Tagihan Glassmorphism -->
                <div style="background-color: var(--primary-subtle); border: 1px solid var(--primary-light); border-radius: var(--radius-lg); padding: var(--space-6);">
                    <h3 style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin-bottom: var(--space-4); border-bottom: 1px dashed var(--primary-light); padding-bottom: var(--space-2);">
                        Kalkulasi Tagihan Cucian
                    </h3>

                    <div style="display: flex; justify-content: space-between; margin-bottom: var(--space-3); font-size: var(--text-sm);">
                        <span style="color: var(--text-muted);">Subtotal Layanan:</span>
                        <strong style="color: var(--text-main);" id="summary-subtotal">Rp 0</strong>
                    </div>

                    <div style="display: flex; justify-content: space-between; margin-bottom: var(--space-3); font-size: var(--text-sm);">
                        <span style="color: var(--text-muted);">Diskon:</span>
                        <span style="color: var(--status-danger); font-weight: var(--font-semibold);">-</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; margin-bottom: var(--space-3); font-size: var(--text-sm);">
                        <span style="color: var(--text-muted);">Biaya Tambahan:</span>
                        <span style="color: var(--status-success); font-weight: var(--font-semibold);">+</span>
                    </div>

                    <div style="border-top: 2px solid var(--border-glass); padding-top: var(--space-4); margin-top: var(--space-4); display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main);">
                            Total Tagihan:
                        </span>
                        <span style="font-size: var(--text-2xl); font-weight: var(--font-extrabold); color: var(--primary);" id="summary-grand-total">
                            Rp 0
                        </span>
                    </div>
                    <div style="font-size: 11px; color: var(--text-light); margin-top: var(--space-2); text-align: right;">
                        * Dihitung ulang dan diverifikasi secara independen oleh server.
                    </div>
                </div>
            </div>
        </div>

        <!-- Tombol Aksi Pengiriman Form -->
        <div style="display: flex; justify-content: flex-end; align-items: center; gap: var(--space-3); margin-bottom: var(--space-8);">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                Batal
            </a>
            <button type="submit" class="btn btn-primary" id="btn-submit-order" style="min-width: 180px;">
                <x-icon name="check" size="18" />
                <span>Simpan Pesanan</span>
            </button>
        </div>
    </form>
</div>

<!-- ===================================================================
     MODAL POPUP: TAMBAH PELANGGAN BARU CEPAT (IN-PLACE AJAX)
     =================================================================== -->
<div class="modal-backdrop" id="modal-quick-customer" role="dialog" aria-modal="true" aria-labelledby="quick-customer-title">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-icon-wrap modal-icon-primary">
                <x-icon name="plus" size="20" />
            </div>
            <div>
                <h3 class="modal-title" id="quick-customer-title">Daftarkan Pelanggan Baru</h3>
                <p class="modal-desc">Pelanggan baru akan langsung terpilih tanpa mereset data item pesanan di belakang.</p>
            </div>
        </div>

        <!-- Error Container Modal -->
        <div id="quick-customer-errors" class="alert alert-danger" style="display: none; margin-bottom: var(--space-4); font-size: var(--text-xs);"></div>

        <form id="form-quick-customer" action="{{ route('customers.store') }}" method="POST">
            @csrf

            <div style="display: flex; flex-direction: column; gap: var(--space-4);">
                <!-- Nama Lengkap -->
                <div>
                    <label for="quick-customer-name" class="form-label" style="font-weight: var(--font-semibold);">
                        Nama Lengkap <span style="color: var(--status-danger);">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           id="quick-customer-name"
                           class="form-control"
                           required
                           minlength="3"
                           maxlength="100"
                           placeholder="Nama pelanggan (misal: Ibu Rina)" />
                </div>

                <!-- Nomor Telepon -->
                <div>
                    <label for="quick-customer-phone" class="form-label" style="font-weight: var(--font-semibold);">
                        Nomor Telepon / WhatsApp <span style="color: var(--status-danger);">*</span>
                    </label>
                    <input type="text"
                           name="phone"
                           id="quick-customer-phone"
                           class="form-control"
                           required
                           placeholder="081234567890" />
                </div>

                <!-- Alamat Domisili -->
                <div>
                    <label for="quick-customer-address" class="form-label" style="font-weight: var(--font-semibold);">
                        Alamat Domisili (Opsional)
                    </label>
                    <textarea name="address"
                              id="quick-customer-address"
                              rows="2"
                              class="form-control"
                              placeholder="Alamat rumah / catatan lokasi..."></textarea>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="btn-close-quick-customer">
                    Batal
                </button>
                <button type="submit" class="btn btn-primary" id="btn-submit-quick-customer">
                    Simpan Pelanggan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===================================================================
     DATA INITIALIZATION & SCRIPT BINDING
     =================================================================== -->
<script>
    window.ORDER_SERVICES = @json($services);
    window.ORDER_OLD_ITEMS = @json(old('items', []));
    window.ORDER_DEFAULT_HOURS = {{ $defaultEstimatedHours }};
    window.ORDER_IS_ADMIN = {{ auth()->user()->isAdmin() ? 'true' : 'false' }};
    window.ORDER_MAX_DISCOUNT_PERCENT = {{ $maxDiscountPercent }};
</script>
<script src="{{ asset('js/order-form.js') }}"></script>
@endsection
