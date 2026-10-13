@extends('layouts.app')

@section('title', 'Laporan Keuangan & Operasional')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6);">
    <!-- Header Halaman & Tombol Ekspor CSV -->
    <div class="glass-card" style="padding: var(--space-6);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: var(--space-4);">
            <div>
                <h2 style="font-size: var(--text-2xl); font-weight: var(--font-bold); color: var(--text-main); margin-bottom: var(--space-1);">
                    Laporan Keuangan & Operasional
                </h2>
                <p style="color: var(--text-muted); font-size: var(--text-sm);">
                    Analisis pembukuan, realisasi arus kas, dan produktivitas layanan usaha laundry &bull; Khusus Administrator / Pemilik
                </p>
            </div>
            <div style="display: flex; gap: var(--space-2); flex-wrap: wrap;" class="no-print">
                <a href="{{ route('admin.reports.payments.export', ['start_date' => $startDateStr, 'end_date' => $endDateStr]) }}" class="btn btn-secondary" title="Unduh Buku Kas Masuk format CSV">
                    <x-icon name="payments" size="16" />
                    <span>Ekspor CSV Kas Masuk</span>
                </a>
                <a href="{{ route('admin.reports.orders.export', ['start_date' => $startDateStr, 'end_date' => $endDateStr]) }}" class="btn btn-primary" title="Unduh Data Pesanan format CSV">
                    <x-icon name="reports" size="16" />
                    <span>Ekspor CSV Transaksi</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Rentang Tanggal Periode Laporan (Task 10.3) -->
    <div class="glass-card" style="padding: var(--space-5);">
        <form method="GET" action="{{ route('admin.reports.index') }}" id="report-filter-form">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: var(--space-4);">
                <div style="display: flex; gap: var(--space-4); flex-wrap: wrap; align-items: flex-end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="report-start-date">Dari Tanggal</label>
                        <input
                            type="date"
                            id="report-start-date"
                            name="start_date"
                            class="form-control"
                            value="{{ $startDateStr }}"
                            required
                        >
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="report-end-date">Sampai Tanggal</label>
                        <input
                            type="date"
                            id="report-end-date"
                            name="end_date"
                            class="form-control"
                            value="{{ $endDateStr }}"
                            required
                        >
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="filter" size="16" />
                        <span>Terapkan Rentang</span>
                    </button>
                </div>

                <!-- Preset Cepat Rentang Tanggal -->
                <div style="display: flex; gap: var(--space-2); font-size: var(--text-xs); align-items: center;" class="no-print">
                    <span style="color: var(--text-muted);">Pintasan:</span>
                    <a href="{{ route('admin.reports.index', ['start_date' => now()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}" class="badge {{ $startDateStr === now()->format('Y-m-d') && $endDateStr === now()->format('Y-m-d') ? 'badge-primary' : 'badge-neutral' }}" style="text-decoration: none;">
                        Hari Ini
                    </a>
                    <a href="{{ route('admin.reports.index', ['start_date' => now()->startOfWeek()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}" class="badge {{ $startDateStr === now()->startOfWeek()->format('Y-m-d') && $endDateStr === now()->format('Y-m-d') ? 'badge-primary' : 'badge-neutral' }}" style="text-decoration: none;">
                        Minggu Ini
                    </a>
                    <a href="{{ route('admin.reports.index', ['start_date' => now()->startOfMonth()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}" class="badge {{ $startDateStr === now()->startOfMonth()->format('Y-m-d') && $endDateStr === now()->format('Y-m-d') ? 'badge-primary' : 'badge-neutral' }}" style="text-decoration: none;">
                        Bulan Ini
                    </a>
                    <a href="{{ route('admin.reports.index', ['start_date' => now()->subDays(30)->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}" class="badge {{ $startDateStr === now()->subDays(30)->format('Y-m-d') && $endDateStr === now()->format('Y-m-d') ? 'badge-primary' : 'badge-neutral' }}" style="text-decoration: none;">
                        30 Hari Terakhir
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- 4 Kartu Metrik Finansial Utama (Task 10.3) -->
    <div class="grid grid-cols-4" style="gap: var(--space-4);">
        <x-stat-card
            title="Jumlah Pesanan"
            :value="$summary['orders_count'] . ' Pesanan'"
            icon="orders"
            color="primary"
            :hint="'Aktif: ' . $summary['active_orders_count'] . ' | Batal: ' . $summary['cancelled_orders_count']"
        />
        <x-stat-card
            title="Nilai Transaksi (Omzet)"
            :value="'Rp' . number_format($summary['gross_revenue'], 0, ',', '.')"
            icon="receipt"
            color="info"
            hint="Total tagihan pesanan masuk periode ini"
        />
        <x-stat-card
            title="Kas Masuk Riil"
            :value="'Rp' . number_format($summary['cash_inflow'], 0, ',', '.')"
            icon="payments"
            color="success"
            hint="Pembayaran valid diterima periode ini"
        />
        <x-stat-card
            title="Sisa Tagihan (Piutang)"
            :value="'Rp' . number_format($summary['outstanding_balance'], 0, ',', '.')"
            icon="alert-triangle"
            color="warning"
            hint="Saldo belum lunas dari pesanan periode ini"
        />
    </div>

    <!-- Panel 1 & 2: Rekapitulasi Metode Pembayaran & Top Layanan -->
    <div class="grid grid-cols-2" style="gap: var(--space-6);">
        <!-- Laporan Pembayaran Berdasarkan Metode (Task 10.3) -->
        <div class="glass-card" style="padding: var(--space-6);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-4);">
                <div>
                    <h3 style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin-bottom: 0.25rem;">
                        Penerimaan Kas per Metode
                    </h3>
                    <p style="font-size: var(--text-xs); color: var(--text-muted);">
                        Arus kas masuk valid berdasarkan kanal pembayaran.
                    </p>
                </div>
                <span class="badge badge-success">
                    Total: Rp{{ number_format($summary['cash_inflow'], 0, ',', '.') }}
                </span>
            </div>

            <div style="display: flex; flex-direction: column; gap: var(--space-3);">
                @foreach($paymentMethods as $pm)
                    <div style="padding: var(--space-3); border-radius: 8px; background: rgba(248, 250, 252, 0.6); border: 1px solid rgba(226, 232, 240, 0.6);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.375rem;">
                            <span style="font-size: var(--text-sm); font-weight: 600; color: var(--text-main);">
                                {{ $pm['method_label'] }}
                                <span style="font-size: 11px; font-weight: normal; color: var(--text-muted);">
                                    ({{ $pm['transaction_count'] }} transaksi)
                                </span>
                            </span>
                            <span style="font-size: var(--text-sm); font-weight: 700; color: var(--color-primary, #2563EB);">
                                {{ $pm['formatted_total'] }}
                            </span>
                        </div>
                        <!-- Progress bar persentase -->
                        <div style="height: 6px; width: 100%; background: #E2E8F0; border-radius: 999px; overflow: hidden;">
                            <div style="height: 100%; width: {{ $pm['percentage'] }}%; background: #2563EB; border-radius: 999px;"></div>
                        </div>
                        <div style="font-size: 10px; color: var(--text-muted); text-align: right; margin-top: 0.25rem;">
                            {{ $pm['percentage'] }}% dari total kas masuk
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Laporan Layanan Paling Sering Dipesan (Top Services) (Task 10.3) -->
        <div class="glass-card" style="padding: var(--space-6);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-4);">
                <div>
                    <h3 style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin-bottom: 0.25rem;">
                        Layanan Paling Sering Dipesan
                    </h3>
                    <p style="font-size: var(--text-xs); color: var(--text-muted);">
                        Peringkat layanan terfavorit dan kontribusi omzet kotor.
                    </p>
                </div>
                <span class="badge badge-info">Top {{ $topServices->count() }}</span>
            </div>

            @if($topServices->isEmpty())
                <x-empty-state
                    title="Belum Ada Data Layanan"
                    description="Tidak ada transaksi layanan yang tercatat dalam periode tanggal ini."
                    icon="services"
                />
            @else
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Layanan</th>
                                <th style="text-align: center;">Frekuensi</th>
                                <th style="text-align: right;">Kuantitas</th>
                                <th style="text-align: right;">Kontribusi Omzet</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topServices as $service)
                                <tr>
                                    <td>
                                        <strong style="color: var(--text-main);">{{ $service['service_name'] }}</strong>
                                        <span style="font-size: 11px; color: var(--text-muted); display: block;">
                                            Satuan: {{ $service['billing_unit'] }}
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge badge-neutral">{{ $service['order_frequency'] }}x</span>
                                    </td>
                                    <td style="text-align: right; font-size: var(--text-sm); font-weight: 500;">
                                        {{ number_format($service['total_quantity'], 2, ',', '.') }} {{ $service['billing_unit'] }}
                                    </td>
                                    <td style="text-align: right; font-weight: 600; color: var(--color-primary, #2563EB);">
                                        {{ $service['formatted_subtotal'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Panel 3: Distribusi Status Pesanan & Status Finansial (Task 10.3) -->
    <div class="grid grid-cols-2" style="gap: var(--space-6);">
        <!-- Distribusi Status Cucian -->
        <div class="glass-card" style="padding: var(--space-6);">
            <h3 style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin-bottom: var(--space-2);">
                Distribusi Status Cucian Periode Ini
            </h3>
            <p style="font-size: var(--text-xs); color: var(--text-muted); margin-bottom: var(--space-4);">
                Tahapan pengerjaan cucian dari pesanan yang masuk dalam rentang tanggal.
            </p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: var(--space-3);">
                @foreach(\App\Enums\LaundryStatus::cases() as $st)
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0.75rem; border-radius: 6px; background: rgba(248, 250, 252, 0.7); border: 1px solid rgba(226, 232, 240, 0.6);">
                        <x-badge-status :status="$st" />
                        <span style="font-size: var(--text-sm); font-weight: 700; color: var(--text-main);">
                            {{ $laundryStatusCounts[$st->value] ?? 0 }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Distribusi Status Pembayaran -->
        <div class="glass-card" style="padding: var(--space-6);">
            <h3 style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin-bottom: var(--space-2);">
                Distribusi Status Pembayaran Periode Ini
            </h3>
            <p style="font-size: var(--text-xs); color: var(--text-muted); margin-bottom: var(--space-4);">
                Tingkat pelunasan tagihan dari pesanan yang masuk dalam rentang tanggal.
            </p>
            <div style="display: flex; flex-direction: column; gap: var(--space-3);">
                @foreach(\App\Enums\PaymentStatus::cases() as $pst)
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0.75rem; border-radius: 6px; background: rgba(248, 250, 252, 0.7); border: 1px solid rgba(226, 232, 240, 0.6);">
                        <x-badge-status :status="$pst" />
                        <span style="font-size: var(--text-sm); font-weight: 700; color: var(--text-main);">
                            {{ $paymentStatusCounts[$pst->value] ?? 0 }} pesanan
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Panel 4: Glosarium & Definisi Angka Laporan (Task 10.3: Wajib Dokumentasikan Definisi) -->
    <div class="glass-card" style="padding: var(--space-6); background: rgba(239, 246, 255, 0.8); border: 1px solid rgba(191, 219, 254, 0.8);">
        <div style="display: flex; align-items: center; gap: var(--space-2); margin-bottom: var(--space-3);">
            <x-icon name="info" size="18" class="text-primary" />
            <h3 style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin: 0;">
                Glosarium & Panduan Definisi Angka Laporan
            </h3>
        </div>
        <p style="font-size: var(--text-xs); color: var(--text-muted); margin-bottom: var(--space-4);">
            Untuk menjaga integritas pembukuan dan akurasi keuangan, sistem menetapkan definisi standar berikut:
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: var(--space-4); font-size: var(--text-xs); line-height: 1.5;">
            <div style="background: white; padding: var(--space-3); border-radius: 6px; border: 1px solid #DBEAFE;">
                <strong style="color: var(--color-primary, #2563EB); display: block; margin-bottom: 0.25rem;">
                    1. Nilai Transaksi (Gross Omzet)
                </strong>
                Total akumulasi tagihan kotor dari pesanan yang masuk pada rentang tanggal <code>received_at</code>. Pesanan yang dibatalkan sah tidak dimasukkan ke dalam nilai transaksi.
            </div>

            <div style="background: white; padding: var(--space-3); border-radius: 6px; border: 1px solid #DBEAFE;">
                <strong style="color: #059669; display: block; margin-bottom: 0.25rem;">
                    2. Kas Masuk Riil (Cash Inflow)
                </strong>
                Total uang fisik atau bukti pembayaran yang diterima kasir pada rentang <code>payment_date</code>. Hanya pembayaran berstatus <strong>Valid</strong> yang dihitung; pembayaran yang di-void <strong>dikecualikan 100%</strong>.
            </div>

            <div style="background: white; padding: var(--space-3); border-radius: 6px; border: 1px solid #DBEAFE;">
                <strong style="color: #D97706; display: block; margin-bottom: 0.25rem;">
                    3. Piutang / Sisa Tagihan
                </strong>
                Total sisa tagihan dari pesanan aktif pada periode tanggal yang belum lunas (Nilai Tagihan dikurangi Pembayaran Valid yang telah masuk).
            </div>

            <div style="background: white; padding: var(--space-3); border-radius: 6px; border: 1px solid #DBEAFE;">
                <strong style="color: #7C3AED; display: block; margin-bottom: 0.25rem;">
                    4. Perbedaan Tanggal Kas vs Tanggal Pesanan
                </strong>
                Uang masuk dihitung dari tanggal kasir menerima pembayaran (<code>payment_date</code>), bukan tanggal pakaian masuk (<code>received_at</code>). Ini menjamin pencatatan kas harian laci kasir cocok dengan uang riil.
            </div>
        </div>
    </div>
</div>
@endsection
