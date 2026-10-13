@extends('layouts.app')

@section('title', 'Dashboard Operasional')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6);">
    <!-- Header Sambutan -->
    <div class="glass-card" style="padding: var(--space-6);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: var(--space-4);">
            <div>
                <h2 style="font-size: var(--text-2xl); font-weight: var(--font-bold); color: var(--text-main); margin-bottom: var(--space-1);">
                    Selamat Datang, {{ auth()->user()->name }}!
                </h2>
                <p style="color: var(--text-muted); font-size: var(--text-sm);">
                    Anda sedang mengelola operasional <strong>LaundryDesk (RAP Laundry)</strong> dengan peran
                    <span class="badge {{ auth()->user()->isAdmin() ? 'badge-primary' : 'badge-info' }}" style="vertical-align: middle;">
                        {{ auth()->user()->role->label() }}
                    </span>.
                </p>
            </div>
            <div style="display: flex; gap: var(--space-2); flex-wrap: wrap;">
                <x-button variant="secondary" icon="print" onclick="window.print()" class="no-print">
                    Cetak Laporan
                </x-button>
                <x-button variant="primary" icon="plus" href="{{ Route::has('orders.create') ? route('orders.create') : '#' }}" class="no-print">
                    Pesanan Baru
                </x-button>
            </div>
        </div>
    </div>

    <!-- Metrik Statistik Utama (Glassmorphism Stat Cards) -->
    <div class="grid grid-cols-4">
        <x-stat-card
            title="Pesanan Hari Ini"
            value="0"
            icon="orders"
            color="primary"
            hint="Total pesanan diterima hari ini"
        />
        <x-stat-card
            title="Sedang Diproses"
            value="0"
            icon="clock"
            color="warning"
            hint="Dicuci, dikeringkan, atau disetrika"
        />
        <x-stat-card
            title="Siap Diambil"
            value="0"
            icon="check"
            color="success"
            hint="Menunggu pengambilan pelanggan"
        />
        <x-stat-card
            title="Pendapatan Hari Ini"
            value="Rp 0"
            icon="payments"
            color="info"
            hint="Total penerimaan kasir hari ini"
        />
    </div>

    <!-- Ringkasan Status & Demonstrasi Komponen UI Reusable -->
    <div class="grid grid-cols-2">
        <!-- Panel Status Cucian & Pembayaran -->
        <div class="glass-card" style="padding: var(--space-6);">
            <h3 style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin-bottom: var(--space-3);">
                Indikator Status Domain Laundry & Kasir
            </h3>
            <p style="font-size: var(--text-xs); color: var(--text-muted); margin-bottom: var(--space-4);">
                Komponen badge status terstandarisasi untuk alur kerja cucian dan status pembayaran kasir.
            </p>
            <div style="display: flex; flex-direction: column; gap: var(--space-3);">
                <div>
                    <span style="font-size: var(--text-xs); font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 0.25rem;">
                        Status Pengerjaan Cucian (LaundryStatus Enum):
                    </span>
                    <div style="display: flex; gap: 0.375rem; flex-wrap: wrap;">
                        <x-badge-status status="diterima" />
                        <x-badge-status status="menunggu_proses" />
                        <x-badge-status status="dicuci" />
                        <x-badge-status status="disetrika" />
                        <x-badge-status status="siap_diambil" />
                        <x-badge-status status="selesai" />
                        <x-badge-status status="dibatalkan" />
                    </div>
                </div>
                <div>
                    <span style="font-size: var(--text-xs); font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 0.25rem;">
                        Status Finansial Kasir (PaymentStatus Enum):
                    </span>
                    <div style="display: flex; gap: 0.375rem; flex-wrap: wrap;">
                        <x-badge-status status="belum_dibayar" />
                        <x-badge-status status="dibayar_sebagian" />
                        <x-badge-status status="lunas" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Filter & Tindakan Berisiko -->
        <div class="glass-card" style="padding: var(--space-6);">
            <h3 style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin-bottom: var(--space-3);">
                Dialog Aksi & Proteksi Konfirmasi
            </h3>
            <p style="font-size: var(--text-xs); color: var(--text-muted); margin-bottom: var(--space-4);">
                Uji coba dialog konfirmasi modal untuk tindakan berisiko (misal pembatalan pesanan / void pembayaran).
            </p>
            <div style="display: flex; gap: var(--space-3); flex-wrap: wrap;">
                <x-button
                    variant="danger"
                    size="sm"
                    icon="alert-triangle"
                    onclick="window.LaundryDesk.openConfirmModal({
                        title: 'Batalkan Pesanan Cucian?',
                        message: 'Tindakan ini akan membatalkan status pengerjaan pesanan dan mencatat jejak audit pembatalan.',
                        confirmText: 'Ya, Batalkan Pesanan',
                        confirmClass: 'btn-danger'
                    })"
                >
                    Uji Modal Batalkan
                </x-button>

                <x-button
                    variant="secondary"
                    size="sm"
                    icon="refresh"
                    onclick="window.location.reload()"
                >
                    Muat Ulang Halaman
                </x-button>
            </div>
        </div>
    </div>

    <!-- Tabel Pesanan Terbaru & Empty State -->
    <div class="glass-card" style="padding: var(--space-6);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-4); flex-wrap: wrap; gap: var(--space-3);">
            <div>
                <h3 style="font-size: var(--text-lg); font-weight: var(--font-bold); color: var(--text-main);">
                    Pesanan Terbaru
                </h3>
                <p style="font-size: var(--text-xs); color: var(--text-muted);">
                    Daftar antrean dan progres cucian pelanggan hari ini.
                </p>
            </div>
            <div class="no-print">
                <x-filter-bar style="padding: 0; background: none; border: none; box-shadow: none; margin: 0;">
                    <div class="filter-group">
                        <x-form.input name="q" placeholder="Cari nomor nota / pelanggan..." style="padding: 0.375rem 0.75rem; font-size: 0.8125rem;" />
                    </div>
                </x-filter-bar>
            </div>
        </div>

        <div class="table-container">
            <x-empty-state
                title="Belum Ada Pesanan Aktif"
                description="Belum ada transaksi cucian yang tercatat untuk hari ini. Silakan buat pesanan baru untuk memulai penerimaan pakaian pelanggan."
                icon="orders"
                actionText="Buat Pesanan Pertama"
                :actionUrl="Route::has('orders.create') ? route('orders.create') : '#'"
            />
        </div>
    </div>
</div>
@endsection
