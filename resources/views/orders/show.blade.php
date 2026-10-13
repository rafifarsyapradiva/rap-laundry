@extends('layouts.app')

@section('title', 'Detail Pesanan ' . $order->order_number)

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6); max-width: 1200px; margin: 0 auto;">

    <!-- Breadcrumb & Top Bar Aksi -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: var(--space-3);" class="no-print">
        <a href="{{ route('orders.create') }}" class="btn btn-secondary btn-sm">
            <x-icon name="arrow-left" size="14" />
            <span>Kembali ke Buat Pesanan</span>
        </a>
        <div style="display: flex; align-items: center; gap: var(--space-2); flex-wrap: wrap;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()" title="Cetak nota pesanan kasir">
                <x-icon name="reports" size="14" />
                <span>Cetak Nota</span>
            </button>
            <a href="{{ route('orders.create') }}" class="btn btn-primary btn-sm">
                <x-icon name="plus" size="14" />
                <span>+ Pesanan Baru</span>
            </a>
        </div>
    </div>

    <!-- Alert Notifikasi Flash Sukses -->
    @if(session('success'))
        <div class="alert alert-success no-print" role="alert">
            <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                <div style="display: flex; align-items: center; gap: var(--space-2);">
                    <x-icon name="check" size="18" />
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" onclick="window.LaundryDesk.dismissAlert(this)" style="background: none; border: none; cursor: pointer; color: inherit;">
                    &times;
                </button>
            </div>
        </div>
    @endif

    <!-- Banner Khusus Jika Pesanan Dibatalkan -->
    @if ($order->is_cancelled)
        <div class="alert alert-danger" role="alert">
            <div style="display: flex; align-items: flex-start; gap: var(--space-3);">
                <x-icon name="alert-triangle" size="22" style="flex-shrink: 0; margin-top: 2px;" />
                <div>
                    <h3 style="font-size: var(--text-base); font-weight: var(--font-bold); margin: 0;">
                        Pesanan Ini Telah Dibatalkan
                    </h3>
                    <p style="margin: var(--space-1) 0 0 0; font-size: var(--text-sm);">
                        Dibatalkan oleh: <strong>{{ $order->canceller?->name ?? 'Administrator' }}</strong> pada
                        {{ $order->cancelled_at ? $order->cancelled_at->format('d-m-Y H:i') : '-' }} WIB.
                    </p>
                    @if ($order->cancellation_reason)
                        <div style="margin-top: var(--space-2); font-size: var(--text-xs); background: rgba(255, 255, 255, 0.6); padding: var(--space-2) var(--space-3); border-radius: var(--radius-sm);">
                            <strong>Alasan Pembatalan:</strong> {{ $order->cancellation_reason }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         KARTU IDENTITAS UTAMA TRANSAKSI
         =================================================================== -->
    <div class="glass-card" style="padding: var(--space-6);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: var(--space-4); border-bottom: 1px solid var(--border-subtle); padding-bottom: var(--space-5);">
            <div>
                <div style="display: flex; align-items: center; gap: var(--space-3); flex-wrap: wrap;">
                    <h1 style="font-size: var(--text-2xl); font-weight: var(--font-extrabold); color: var(--text-main); margin: 0; letter-spacing: -0.02em;">
                        {{ $order->order_number }}
                    </h1>
                    <x-badge-status :status="$order->laundry_status" />
                    <x-badge-status :status="$order->payment_status" />
                </div>
                <div style="color: var(--text-muted); font-size: var(--text-xs); margin-top: var(--space-2); display: flex; align-items: center; gap: var(--space-3); flex-wrap: wrap;">
                    <span>Petugas Penerima: <strong>{{ $order->creator->name }}</strong> ({{ $order->creator->role->label() }})</span>
                    <span>&bull;</span>
                    <span>Dibuat: {{ $order->created_at->format('d-m-Y H:i') }} WIB</span>
                </div>
            </div>

            <div style="display: flex; gap: var(--space-3); align-items: center;" class="no-print">
                <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
                    <x-icon name="reports" size="14" />
                    <span>Cetak Nota</span>
                </button>
            </div>
        </div>

        <!-- Grid Metadata Waktu -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--space-4); margin-top: var(--space-5);">
            <div style="background-color: var(--primary-subtle); border-radius: var(--radius-md); padding: var(--space-3) var(--space-4);">
                <div style="font-size: var(--text-xs); color: var(--text-muted); text-transform: uppercase; font-weight: var(--font-bold); letter-spacing: 0.05em;">
                    Waktu Masuk
                </div>
                <div style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin-top: var(--space-1);">
                    {{ $order->received_at->format('d-m-Y H:i') }} WIB
                </div>
            </div>

            <div style="background-color: var(--primary-subtle); border-radius: var(--radius-md); padding: var(--space-3) var(--space-4);">
                <div style="font-size: var(--text-xs); color: var(--text-muted); text-transform: uppercase; font-weight: var(--font-bold); letter-spacing: 0.05em;">
                    Target Estimasi Selesai
                </div>
                <div style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--primary); margin-top: var(--space-1);">
                    {{ $order->estimated_completion_at ? $order->estimated_completion_at->format('d-m-Y H:i') . ' WIB' : 'Sesuai antrean' }}
                </div>
            </div>

            <div style="background-color: var(--primary-subtle); border-radius: var(--radius-md); padding: var(--space-3) var(--space-4);">
                <div style="font-size: var(--text-xs); color: var(--text-muted); text-transform: uppercase; font-weight: var(--font-bold); letter-spacing: 0.05em;">
                    Waktu Selesai Aktual
                </div>
                <div style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin-top: var(--space-1);">
                    {{ $order->completed_at ? $order->completed_at->format('d-m-Y H:i') . ' WIB' : 'Belum selesai' }}
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================
         KONTEN DUA KOLOM: DATA LAYANAN & FINANSIAL
         =================================================================== -->
    <div style="display: grid; grid-template-columns: 1fr 380px; gap: var(--space-6); align-items: start;" class="order-detail-layout">

        <!-- ======================= KOLOM KIRI ======================= -->
        <div style="display: flex; flex-direction: column; gap: var(--space-6);">

            <!-- 1. Kartu Profil Pelanggan -->
            <div class="glass-card" style="padding: var(--space-5);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-3);">
                    <h2 style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin: 0; display: flex; align-items: center; gap: var(--space-2);">
                        <x-icon name="customers" size="18" class="icon-primary" />
                        <span>Data Pelanggan</span>
                    </h2>
                    <a href="{{ route('customers.show', $order->customer_id) }}" class="btn btn-secondary btn-sm no-print" style="font-size: 11px;">
                        <span>Profil Lengkap</span>
                    </a>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: var(--space-3);">
                    <div>
                        <div style="font-size: var(--text-lg); font-weight: var(--font-bold); color: var(--text-main);">
                            {{ $order->customer->name }}
                            <span class="badge badge-primary" style="margin-left: var(--space-2); font-size: 11px;">
                                {{ $order->customer->customer_code }}
                            </span>
                        </div>
                        <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-2); display: flex; flex-direction: column; gap: var(--space-1);">
                            <div>
                                <strong>No. Telepon / WA:</strong>
                                <a href="tel:{{ $order->customer->phone }}" style="color: var(--primary); text-decoration: none; font-weight: var(--font-semibold);">
                                    {{ $order->customer->formatted_phone }}
                                </a>
                            </div>
                            <div>
                                <strong>Alamat:</strong>
                                <span>{{ $order->customer->address ?? 'Tidak ada catatan alamat.' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Rincian Item Layanan (Snapshot Data) -->
            <div class="glass-card" style="padding: var(--space-5);">
                <div style="margin-bottom: var(--space-4);">
                    <h2 style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin: 0; display: flex; align-items: center; gap: var(--space-2);">
                        <x-icon name="services" size="18" class="icon-primary" />
                        <span>Rincian Layanan Cucian (Snapshot Transaksi)</span>
                    </h2>
                    <p style="color: var(--text-muted); font-size: var(--text-xs); margin-top: var(--space-1);">
                        Nilai snapshot dikunci permanen saat transaksi dibuat untuk melindungi data historis.
                    </p>
                </div>

                <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                    <table class="table-glass" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">No</th>
                                <th>Layanan</th>
                                <th style="text-align: center; width: 80px;">Satuan</th>
                                <th style="text-align: right; width: 120px;">Kuantitas</th>
                                <th style="text-align: right; width: 130px;">Tarif Satuan</th>
                                <th style="text-align: right; width: 130px;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $idx => $item)
                                <tr>
                                    <td style="text-align: center; color: var(--text-muted); vertical-align: top;">
                                        {{ $idx + 1 }}
                                    </td>
                                    <td style="vertical-align: top;">
                                        <div style="font-weight: var(--font-bold); color: var(--text-main);">
                                            {{ $item->service_name_snapshot }}
                                        </div>
                                        @if ($item->notes)
                                            <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: 2px; font-style: italic;">
                                                Catatan: {{ $item->notes }}
                                            </div>
                                        @endif
                                    </td>
                                    <td style="text-align: center; vertical-align: top;">
                                        <span class="badge {{ $item->billing_unit_snapshot->value === 'kg' ? 'badge-primary' : 'badge-neutral' }}">
                                            {{ $item->billing_unit_snapshot->label() }}
                                        </span>
                                    </td>
                                    <td style="text-align: right; font-weight: var(--font-semibold); vertical-align: top;">
                                        {{ rtrim(rtrim(number_format((float) $item->quantity, 2, ',', '.'), '0'), ',') }}{{ $item->billing_unit_snapshot->value === 'kg' ? ' kg' : '' }}
                                    </td>
                                    <td style="text-align: right; color: var(--text-muted); vertical-align: top;">
                                        Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: right; font-weight: var(--font-bold); color: var(--primary); vertical-align: top;">
                                        Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Catatan Khusus Pesanan -->
                @if ($order->notes)
                    <div style="margin-top: var(--space-4); background-color: var(--primary-subtle); border-radius: var(--radius-md); padding: var(--space-3) var(--space-4);">
                        <strong style="font-size: var(--text-xs); text-transform: uppercase; color: var(--primary-dark);">Catatan Pesanan:</strong>
                        <div style="font-size: var(--text-sm); color: var(--text-main); margin-top: 2px;">
                            {{ $order->notes }}
                        </div>
                    </div>
                @endif
            </div>

            <!-- 3. Riwayat Pembayaran Tercatat -->
            <div class="glass-card" style="padding: var(--space-5);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-4);">
                    <h2 style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin: 0; display: flex; align-items: center; gap: var(--space-2);">
                        <x-icon name="payments" size="18" class="icon-primary" />
                        <span>Riwayat Pembayaran Masuk</span>
                    </h2>
                    <span class="badge {{ $order->is_paid ? 'badge-success' : 'badge-warning' }}" style="font-size: 11px;">
                        {{ $order->payment_status->label() }}
                    </span>
                </div>

                @if ($order->payments->isNotEmpty())
                    <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                        <table class="table-glass" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Metode</th>
                                    <th>Referensi</th>
                                    <th>Petugas</th>
                                    <th style="text-align: right;">Nominal</th>
                                    <th style="text-align: center;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->payments as $payment)
                                    <tr style="{{ $payment->isVoided() ? 'opacity: 0.5; text-decoration: line-through;' : '' }}">
                                        <td style="font-size: var(--text-xs);">
                                            {{ $payment->payment_date->format('d-m-Y H:i') }}
                                        </td>
                                        <td>
                                            {{ $payment->method->label() }}
                                        </td>
                                        <td style="font-size: var(--text-xs); color: var(--text-muted);">
                                            {{ $payment->reference ?: '-' }}
                                        </td>
                                        <td style="font-size: var(--text-xs);">
                                            {{ $payment->recorder->name }}
                                        </td>
                                        <td style="text-align: right; font-weight: var(--font-bold); color: var(--status-success);">
                                            Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}
                                        </td>
                                        <td style="text-align: center;">
                                            @if ($payment->isValid())
                                                <span class="badge badge-success" style="font-size: 10px;">Valid</span>
                                            @else
                                                <span class="badge badge-danger" style="font-size: 10px;" title="{{ $payment->void_reason }}">Void</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="text-align: center; padding: var(--space-6); background-color: var(--primary-subtle); border-radius: var(--radius-md); color: var(--text-muted); font-size: var(--text-sm);">
                        Belum ada pembayaran yang dicatat untuk pesanan ini.
                    </div>
                @endif
            </div>
        </div>

        <!-- ======================= KOLOM KANAN ======================= -->
        <div style="display: flex; flex-direction: column; gap: var(--space-6);">

            <!-- 1. Ringkasan Finansial Tagihan -->
            <div class="glass-card" style="padding: var(--space-6);">
                <h3 style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin-bottom: var(--space-4); border-bottom: 1px solid var(--border-subtle); padding-bottom: var(--space-2);">
                    Rincian Tagihan Finansial
                </h3>

                <div style="display: flex; flex-direction: column; gap: var(--space-3); font-size: var(--text-sm);">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Subtotal Layanan:</span>
                        <span style="font-weight: var(--font-semibold); color: var(--text-main);">
                            Rp {{ number_format((float) $order->subtotal, 0, ',', '.') }}
                        </span>
                    </div>

                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Potongan Diskon:</span>
                        <span style="font-weight: var(--font-semibold); color: var(--status-danger);">
                            - Rp {{ number_format((float) $order->discount_amount, 0, ',', '.') }}
                        </span>
                    </div>

                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Biaya Tambahan:</span>
                        <span style="font-weight: var(--font-semibold); color: var(--status-success);">
                            + Rp {{ number_format((float) $order->extra_charge, 0, ',', '.') }}
                        </span>
                    </div>

                    <div style="border-top: 1px dashed var(--border-subtle); margin: var(--space-2) 0;"></div>

                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: var(--font-bold); color: var(--text-main);">Total Tagihan:</span>
                        <span style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--primary);">
                            Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}
                        </span>
                    </div>

                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Total Dibayar (Valid):</span>
                        <span style="font-weight: var(--font-bold); color: var(--status-success);">
                            Rp {{ number_format($order->total_paid, 0, ',', '.') }}
                        </span>
                    </div>

                    <div style="border-top: 2px solid var(--border-glass); padding-top: var(--space-3); margin-top: var(--space-2); display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: var(--font-bold); color: var(--text-main);">Sisa Tagihan:</span>
                        @if ($order->remaining_balance <= 0)
                            <span class="badge badge-success" style="font-size: var(--text-sm); font-weight: var(--font-bold);">
                                Lunas (Rp 0)
                            </span>
                        @else
                            <span style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--status-danger);">
                                Rp {{ number_format($order->remaining_balance, 0, ',', '.') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 2. Timeline Kronologis Riwayat Status Cucian -->
            <div class="glass-card" style="padding: var(--space-6);">
                <h3 style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin-bottom: var(--space-4); border-bottom: 1px solid var(--border-subtle); padding-bottom: var(--space-2);">
                    Riwayat Alur Status
                </h3>

                <div style="display: flex; flex-direction: column; gap: var(--space-4); position: relative;">
                    @foreach ($order->statusHistories as $hist)
                        <div style="display: flex; gap: var(--space-3); align-items: flex-start;">
                            <div style="width: 8px; height: 8px; border-radius: var(--radius-full); background-color: var(--primary); margin-top: 6px; flex-shrink: 0;"></div>
                            <div style="display: flex; flex-direction: column; gap: 2px; width: 100%;">
                                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: var(--space-2);">
                                    <x-badge-status :status="$hist->to_status" />
                                    <span style="font-size: 11px; color: var(--text-muted);">
                                        {{ $hist->created_at->format('d/m/Y H:i') }}
                                    </span>
                                </div>
                                <div style="font-size: var(--text-xs); color: var(--text-main); margin-top: 2px;">
                                    Oleh: <strong>{{ $hist->changer->name }}</strong>
                                </div>
                                @if ($hist->note)
                                    <div style="font-size: 11px; color: var(--text-muted); font-style: italic; background: rgba(0,0,0,0.03); padding: 2px 6px; border-radius: var(--radius-sm); margin-top: 2px;">
                                        {{ $hist->note }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- 3. Aksi Cepat / Navigasi -->
            <div class="glass-card no-print" style="padding: var(--space-5);">
                <h3 style="font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-main); margin-bottom: var(--space-3);">
                    Aksi Transaksi
                </h3>
                <div style="display: flex; flex-direction: column; gap: var(--space-2);">
                    <button type="button" class="btn btn-secondary btn-sm" style="width: 100%;" onclick="window.print()">
                        <x-icon name="reports" size="14" />
                        <span>Cetak Nota Transaksi</span>
                    </button>
                    <a href="{{ route('orders.create') }}" class="btn btn-primary btn-sm" style="width: 100%;">
                        <x-icon name="plus" size="14" />
                        <span>Buat Pesanan Baru</span>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
@media (max-width: 900px) {
    .order-detail-layout {
        grid-template-columns: 1fr !important;
    }
}
</style>
@endsection
