@extends('layouts.app')

@section('title', 'Dashboard Operasional')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6);">
    <!-- Header Sambutan & Quick Actions -->
    <div class="glass-card" style="padding: var(--space-6);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: var(--space-4);">
            <div>
                <h2 style="font-size: var(--text-2xl); font-weight: var(--font-bold); color: var(--text-main); margin-bottom: var(--space-1);">
                    Selamat Datang, {{ auth()->user()->name }}!
                </h2>
                <p style="color: var(--text-muted); font-size: var(--text-sm);">
                    Ringkasan operasional harian <strong>LaundryDesk (RAP Laundry)</strong> &mdash; Peran:
                    <span class="badge {{ auth()->user()->isAdmin() ? 'badge-primary' : 'badge-info' }}" style="vertical-align: middle;">
                        {{ auth()->user()->role->label() }}
                    </span>
                    &bull; {{ now()->translatedFormat('l, d F Y') }}
                </p>
            </div>
            <div style="display: flex; gap: var(--space-2); flex-wrap: wrap;" class="no-print">
                <x-button variant="secondary" icon="orders" href="{{ route('orders.index') }}">
                    Daftar Pesanan
                </x-button>
                @if(auth()->user()->isAdmin())
                    <x-button variant="secondary" icon="reports" href="{{ route('admin.reports.index') }}">
                        Laporan Keuangan
                    </x-button>
                @endif
                <x-button variant="primary" icon="plus" href="{{ route('orders.create') }}">
                    Pesanan Baru
                </x-button>
            </div>
        </div>
    </div>

    <!-- Metrik Statistik Utama (Task 10.1: 6 Metrik Riil Database) -->
    <div class="grid grid-cols-3" style="gap: var(--space-4);">
        <x-stat-card
            title="Pesanan Hari Ini"
            :value="$metrics['orders_today_count']"
            icon="orders"
            color="primary"
            hint="Total pesanan diterima hari ini"
        />
        <x-stat-card
            title="Sedang Diproses"
            :value="$metrics['processing_orders_count']"
            icon="clock"
            color="warning"
            hint="Antrean cuci, kering, atau setrika"
        />
        <x-stat-card
            title="Siap Diambil"
            :value="$metrics['ready_orders_count']"
            icon="check"
            color="info"
            hint="Cucian selesai & menunggu pelanggan"
        />
        <x-stat-card
            title="Sudah Diambil (Selesai)"
            :value="$metrics['completed_orders_count']"
            icon="receipt"
            color="success"
            :hint="'Diambil hari ini: ' . $metrics['completed_today_count'] . ' pesanan'"
        />
        <x-stat-card
            title="Kas Masuk Hari Ini"
            :value="'Rp' . number_format($metrics['cash_inflow_today'], 0, ',', '.')"
            icon="payments"
            color="success"
            hint="Pembayaran valid diterima hari ini"
        />
        <x-stat-card
            title="Total Piutang Belum Lunas"
            :value="'Rp' . number_format($metrics['total_unpaid_balance'], 0, ',', '.')"
            icon="alert-triangle"
            color="danger"
            hint="Sisa tagihan pesanan aktif"
        />
    </div>

    <!-- Grafik Sederhana 100% Offline & Ringkasan Distribusi Status -->
    <div class="grid grid-cols-3" style="gap: var(--space-6);">
        <!-- Grafik Tren 7 Hari Terakhir (Offline Pure SVG) -->
        <div class="glass-card" style="padding: var(--space-6); grid-column: span 2;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-4);">
                <div>
                    <h3 style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin-bottom: 0.25rem;">
                        Tren Operasional 7 Hari Terakhir
                    </h3>
                    <p style="font-size: var(--text-xs); color: var(--text-muted);">
                        Volume pesanan masuk dan realisasi kas masuk harian (100% offline).
                    </p>
                </div>
                <div style="display: flex; gap: var(--space-3); font-size: var(--text-xs);">
                    <span style="display: inline-flex; align-items: center; gap: 0.375rem; color: var(--text-muted);">
                        <span style="display: inline-block; width: 10px; height: 10px; border-radius: 2px; background: #2563EB;"></span>
                        Pesanan (Qty)
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 0.375rem; color: var(--text-muted);">
                        <span style="display: inline-block; width: 10px; height: 10px; border-radius: 2px; background: #10B981;"></span>
                        Kas Masuk (Rp)
                    </span>
                </div>
            </div>

            @php
                $maxOrders = max(1, $weeklyTrend->max('orders_count'));
                $maxCash = max(1, $weeklyTrend->max('cash_amount'));
            @endphp

            <!-- Pure SVG & CSS Responsive Bar Chart -->
            <div style="padding: var(--space-2) 0; overflow-x: auto;">
                <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: var(--space-3); align-items: flex-end; min-height: 180px; padding: 0 var(--space-2);">
                    @foreach($weeklyTrend as $day)
                        @php
                            $orderHeightPercent = min(100, max(8, round(($day['orders_count'] / $maxOrders) * 90)));
                            $cashHeightPercent = $day['cash_amount'] > 0 ? min(100, max(8, round(($day['cash_amount'] / $maxCash) * 90))) : 0;
                        @endphp
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 0.375rem; height: 100%; justify-content: flex-end;">
                            <!-- Kolom Batang Visual -->
                            <div style="display: flex; align-items: flex-end; gap: 4px; width: 100%; height: 140px; background: rgba(226, 232, 240, 0.25); border-radius: 6px; padding: 4px; justify-content: center;">
                                <!-- Bar Pesanan -->
                                <div
                                    title="{{ $day['label'] }}: {{ $day['orders_count'] }} pesanan"
                                    style="width: 14px; height: {{ $day['orders_count'] > 0 ? $orderHeightPercent : 4 }}%; background: {{ $day['is_today'] ? '#1D4ED8' : '#3B82F6' }}; border-radius: 3px 3px 0 0; transition: height 0.3s ease;"
                                ></div>
                                <!-- Bar Kas Masuk -->
                                <div
                                    title="{{ $day['label'] }}: {{ $day['formatted_cash'] }}"
                                    style="width: 14px; height: {{ $cashHeightPercent }}%; background: {{ $day['is_today'] ? '#059669' : '#10B981' }}; border-radius: 3px 3px 0 0; transition: height 0.3s ease;"
                                ></div>
                            </div>
                            <!-- Angka & Label Hari -->
                            <span style="font-size: 11px; font-weight: {{ $day['is_today'] ? '700' : '500' }}; color: {{ $day['is_today'] ? 'var(--color-primary)' : 'var(--text-muted)' }}; text-align: center;">
                                {{ $day['short_label'] }}
                                @if($day['is_today'])
                                    <span style="display: block; font-size: 9px; color: var(--color-primary); font-weight: 700;">Hari Ini</span>
                                @else
                                    <span style="display: block; font-size: 9px; color: var(--text-muted);">{{ $day['orders_count'] }} psn</span>
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Distribusi Status Cucian Aktif -->
        <div class="glass-card" style="padding: var(--space-6);">
            <h3 style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin-bottom: var(--space-3);">
                Distribusi Status Cucian
            </h3>
            <p style="font-size: var(--text-xs); color: var(--text-muted); margin-bottom: var(--space-4);">
                Akumulasi seluruh pesanan berdasarkan tahapan operasional.
            </p>
            <div style="display: flex; flex-direction: column; gap: var(--space-2);">
                @foreach(\App\Enums\LaundryStatus::cases() as $st)
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.375rem 0.5rem; border-radius: 6px; background: rgba(248, 250, 252, 0.6);">
                        <x-badge-status :status="$st" />
                        <span style="font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-main);">
                            {{ $statusDistribution[$st->value] ?? 0 }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Tabel 10 Transaksi Terbaru (Task 10.1: Riil Database) -->
    <div class="glass-card" style="padding: var(--space-6);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-4); flex-wrap: wrap; gap: var(--space-3);">
            <div>
                <h3 style="font-size: var(--text-lg); font-weight: var(--font-bold); color: var(--text-main);">
                    Transaksi Terbaru
                </h3>
                <p style="font-size: var(--text-xs); color: var(--text-muted);">
                    10 pesanan cucian terakhir yang dicatat ke sistem.
                </p>
            </div>
            <div class="no-print">
                <x-button variant="secondary" size="sm" icon="orders" href="{{ route('orders.index') }}">
                    Lihat Semua Transaksi &rarr;
                </x-button>
            </div>
        </div>

        @if($recentOrders->isEmpty())
            <div class="table-container">
                <x-empty-state
                    title="Belum Ada Pesanan"
                    description="Belum ada transaksi cucian yang tercatat di database. Klik tombol di bawah untuk membuat pesanan pertama."
                    icon="orders"
                    actionText="Buat Pesanan Pertama"
                    :actionUrl="route('orders.create')"
                />
            </div>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nomor Nota</th>
                            <th>Pelanggan</th>
                            <th>Tanggal Masuk</th>
                            <th>Status Cucian</th>
                            <th>Status Pembayaran</th>
                            <th style="text-align: right;">Total Tagihan</th>
                            <th style="text-align: right;">Sisa Tagihan</th>
                            <th>Petugas</th>
                            <th style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('orders.show', $order) }}" style="font-weight: 600; color: var(--color-primary); text-decoration: none;">
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td>
                                    <div style="font-weight: 500; color: var(--text-main);">
                                        {{ $order->customer->name ?? '-' }}
                                    </div>
                                    <div style="font-size: 11px; color: var(--text-muted);">
                                        {{ $order->customer->phone ?? '' }}
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: var(--text-xs); color: var(--text-main);">
                                        {{ $order->received_at->format('d/m/Y') }}
                                    </div>
                                    <div style="font-size: 11px; color: var(--text-muted);">
                                        {{ $order->received_at->format('H:i') }} WIB
                                    </div>
                                </td>
                                <td>
                                    <x-badge-status :status="$order->laundry_status" />
                                </td>
                                <td>
                                    <x-badge-status :status="$order->payment_status" />
                                </td>
                                <td style="text-align: right; font-weight: 600; color: var(--text-main);">
                                    {{ $order->formatted_total_amount }}
                                </td>
                                <td style="text-align: right; font-weight: 600; color: {{ $order->remaining_balance > 0 ? 'var(--color-danger, #DC2626)' : 'var(--color-success, #16A34A)' }};">
                                    {{ $order->formatted_remaining_balance }}
                                </td>
                                <td>
                                    <span style="font-size: var(--text-xs); color: var(--text-muted);">
                                        {{ $order->creator->name ?? '-' }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 0.375rem;">
                                        <x-button variant="secondary" size="sm" icon="eye" href="{{ route('orders.show', $order) }}" title="Rincian Pesanan" />
                                        <x-button variant="secondary" size="sm" icon="print" href="{{ route('orders.print', $order) }}" title="Cetak Nota" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
