@extends('layouts.app')

@section('title', 'Ekspor Laporan Keuangan')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6);">
    <div class="glass-card" style="padding: var(--space-6);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-4);">
            <div>
                <h2 style="font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-main);">
                    Ekspor Laporan Keuangan & Transaksi
                </h2>
                <p style="color: var(--text-muted); font-size: var(--text-sm); margin-top: var(--space-1);">
                    Unduh rekapitulasi pendapatan, piutang, dan riwayat pesanan (Khusus Pemilik/Administrator).
                </p>
            </div>
            <span class="badge badge-primary">Khusus Administrator</span>
        </div>
    </div>

    <div class="grid grid-cols-2">
        <x-stat-card
            title="Format Data Tersedia"
            value="CSV & Print"
            icon="reports"
            color="primary"
            hint="Dapat dibuka offline dengan aplikasi spreadsheet lokal"
        />
        <x-stat-card
            title="Tingkat Hak Akses"
            value="Restricted"
            icon="settings"
            color="warning"
            hint="Kasir dibatasi dari akses data finansial agregat"
        />
    </div>

    <!-- Hub Navigasi Cepat Laporan & Ekspor -->
    <div class="glass-card" style="padding: var(--space-6);">
        <h3 style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin-bottom: var(--space-4);">
            Pusat Unduhan & Dasbor Analitik
        </h3>
        <div style="display: flex; gap: var(--space-3); flex-wrap: wrap;">
            <a href="{{ route('admin.reports.index') }}" class="btn btn-primary">
                <x-icon name="reports" size="16" />
                <span>Buka Dasbor Laporan Lengkap</span>
            </a>
            <a href="{{ route('admin.reports.orders.export') }}" class="btn btn-secondary">
                <x-icon name="orders" size="16" />
                <span>Unduh CSV Riwayat Transaksi</span>
            </a>
            <a href="{{ route('admin.reports.payments.export') }}" class="btn btn-secondary">
                <x-icon name="payments" size="16" />
                <span>Unduh CSV Buku Kas Masuk</span>
            </a>
        </div>
    </div>
</div>
@endsection
