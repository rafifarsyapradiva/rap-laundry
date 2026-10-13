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

    <!-- Alert Notifikasi Error Validasi / Bisnis Transisi -->
    @if ($errors->any())
        <div class="alert alert-danger no-print" role="alert">
            <div style="display: flex; align-items: flex-start; justify-content: space-between; width: 100%;">
                <div style="display: flex; align-items: flex-start; gap: var(--space-2);">
                    <x-icon name="alert-triangle" size="18" style="flex-shrink: 0; margin-top: 2px;" />
                    <div>
                        <strong style="font-size: var(--text-sm);">Gagal Memproses Permintaan:</strong>
                        <ul style="margin: var(--space-1) 0 0 var(--space-4); font-size: var(--text-xs); padding: 0;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
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
         PANEL KONTROL STATUS OPERASIONAL CUCIAN & AKSI TRANSISI
         =================================================================== -->
    <div class="glass-card no-print" style="padding: var(--space-6);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: var(--space-4); border-bottom: 1px solid var(--border-subtle); padding-bottom: var(--space-4); margin-bottom: var(--space-5);">
            <div>
                <h2 style="font-size: var(--text-lg); font-weight: var(--font-bold); color: var(--text-main); margin: 0; display: flex; align-items: center; gap: var(--space-2);">
                    <x-icon name="dashboard" size="20" class="icon-primary" />
                    <span>Alur Status Pengerjaan Cucian</span>
                </h2>
                <p style="font-size: var(--text-xs); color: var(--text-muted); margin: var(--space-1) 0 0 0;">
                    @if ($order->is_cancelled)
                        Pesanan ini telah dibatalkan dan proses operasional dihentikan.
                    @elseif ($order->laundry_status === \App\Enums\LaundryStatus::Selesai)
                        Pesanan telah selesai dan cucian telah diserahkan kepada pelanggan.
                    @else
                        Status saat ini: <strong>{{ $order->laundry_status->label() }}</strong>. Perbarui status pengerjaan sesuai alur operasional.
                    @endif
                </p>
            </div>

            <!-- Tombol Aksi Transisi -->
            @if (! $order->isTerminal())
                <div style="display: flex; align-items: center; gap: var(--space-2); flex-wrap: wrap;">
                    @php
                        $nextStatus = $order->getNextRecommendedStatus();
                        $otherTransitions = array_filter(
                            $order->getAvailableTransitions(),
                            fn($st) => $st !== $nextStatus && $st !== \App\Enums\LaundryStatus::Dibatalkan
                        );
                    @endphp

                    @if ($nextStatus)
                        <button type="button" class="btn btn-primary" onclick="openStatusModal('{{ $nextStatus->value }}', '{{ $nextStatus->label() }}', {{ $order->remaining_balance }})" title="Perbarui status langsung ke tahap berikutnya">
                            <x-icon name="check" size="16" />
                            <span>Lanjut ke: {{ $nextStatus->label() }}</span>
                        </button>
                    @endif

                    @if (! empty($otherTransitions))
                        <div style="position: relative; display: inline-block;">
                            <button type="button" class="btn btn-secondary" onclick="toggleStatusDropdown(event)" title="Pilih transisi status alternatif">
                                <span>Pilihan Lainnya</span>
                                <span style="font-size: 10px; margin-left: 4px;">▼</span>
                            </button>
                            <div id="status-alt-dropdown" class="glass-card" style="display: none; position: absolute; right: 0; top: calc(100% + 6px); z-index: 50; min-width: 200px; padding: var(--space-2); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15); border: 1px solid var(--border-glass);">
                                <div style="font-size: 11px; font-weight: var(--font-bold); color: var(--text-muted); padding: var(--space-1) var(--space-2); text-transform: uppercase;">Lompat ke status:</div>
                                @foreach ($otherTransitions as $transition)
                                    <button type="button" class="btn btn-secondary btn-sm" style="width: 100%; text-align: left; justify-content: flex-start; margin-bottom: 4px; font-size: 12px;" onclick="openStatusModal('{{ $transition->value }}', '{{ $transition->label() }}', {{ $order->remaining_balance }}); closeStatusDropdown();">
                                        <x-badge-status :status="$transition" style="font-size: 11px;" />
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <button type="button" class="btn btn-danger btn-sm" onclick="openCancelModal()" title="Batalkan pesanan ini">
                        <x-icon name="alert-triangle" size="14" />
                        <span>Batalkan</span>
                    </button>
                </div>
            @endif
        </div>

        <!-- Stepper Visualisasi Alur Status -->
        @php
            $stages = [
                ['status' => \App\Enums\LaundryStatus::Diterima, 'required' => true, 'step' => 1],
                ['status' => \App\Enums\LaundryStatus::MenungguProses, 'required' => true, 'step' => 2],
                ['status' => \App\Enums\LaundryStatus::Dicuci, 'required' => $order->requiresWashing(), 'step' => 3],
                ['status' => \App\Enums\LaundryStatus::Dikeringkan, 'required' => $order->requiresWashing(), 'step' => 4],
                ['status' => \App\Enums\LaundryStatus::Disetrika, 'required' => $order->requiresIroning(), 'step' => 5],
                ['status' => \App\Enums\LaundryStatus::SiapDiambil, 'required' => true, 'step' => 6],
                ['status' => \App\Enums\LaundryStatus::Selesai, 'required' => true, 'step' => 7],
            ];

            $orderRank = [
                'diterima' => 1,
                'menunggu_proses' => 2,
                'dicuci' => 3,
                'dikeringkan' => 4,
                'disetrika' => 5,
                'siap_diambil' => 6,
                'selesai' => 7,
            ];

            $currentRank = $orderRank[$order->laundry_status->value] ?? 0;
        @endphp

        <div class="status-pipeline-wrap" style="display: flex; align-items: flex-start; justify-content: space-between; position: relative; overflow-x: auto; padding: var(--space-3) var(--space-2); -webkit-overflow-scrolling: touch; gap: var(--space-2);">
            @foreach ($stages as $index => $stage)
                @php
                    $isCurrent = ($order->laundry_status === $stage['status']);
                    $isPast = ($currentRank > $stage['step']) && ! $order->is_cancelled;
                    $isBypassed = (! $stage['required']);
                @endphp

                <div class="pipeline-step {{ $isCurrent ? 'is-current' : '' }} {{ $isPast ? 'is-past' : '' }} {{ $isBypassed ? 'is-bypassed' : '' }}" style="display: flex; flex-direction: column; align-items: center; text-align: center; min-width: 95px; flex: 1; position: relative; z-index: 1;">
                    <!-- Circle Indicator -->
                    <div style="width: 36px; height: 36px; border-radius: var(--radius-full); display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: var(--font-bold); transition: all 0.2s ease;
                        @if ($order->is_cancelled)
                            background-color: var(--status-danger-bg); color: var(--status-danger); border: 2px solid var(--status-danger);
                        @elseif ($isCurrent)
                            background-color: var(--primary); color: #ffffff; border: 3px solid var(--primary-light); box-shadow: 0 0 0 4px var(--primary-subtle);
                        @elseif ($isPast)
                            background-color: var(--primary-subtle); color: var(--primary); border: 2px solid var(--primary);
                        @elseif ($isBypassed)
                            background-color: rgba(0,0,0,0.03); color: var(--text-muted); border: 2px dashed var(--border-subtle); opacity: 0.6;
                        @else
                            background-color: rgba(255,255,255,0.7); color: var(--text-muted); border: 2px solid var(--border-subtle);
                        @endif
                    ">
                        @if ($isPast)
                            <x-icon name="check" size="16" />
                        @elseif ($isBypassed)
                            &ndash;
                        @else
                            {{ $stage['step'] }}
                        @endif
                    </div>

                    <!-- Label Status -->
                    <div style="margin-top: var(--space-2); font-size: var(--text-xs); font-weight: {{ $isCurrent ? 'var(--font-bold)' : 'var(--font-semibold)' }}; color: {{ $isCurrent ? 'var(--primary)' : ($isPast ? 'var(--text-main)' : 'var(--text-muted)') }};">
                        {{ $stage['status']->label() }}
                    </div>

                    <!-- Keterangan Bypass / Khusus -->
                    @if ($isBypassed)
                        <div style="font-size: 10px; color: var(--text-muted); margin-top: 2px; font-style: italic;">
                            (Dilewati)
                        </div>
                    @elseif ($isCurrent)
                        <div style="font-size: 10px; color: var(--primary); margin-top: 2px; font-weight: var(--font-bold);">
                            &bull; Saat Ini
                        </div>
                    @endif
                </div>

                @if ($index < count($stages) - 1)
                    <!-- Garis Penghubung Pipeline -->
                    <div style="flex: 1; height: 3px; background-color: {{ ($currentRank > $stage['step'] && ! $order->is_cancelled) ? 'var(--primary)' : 'var(--border-subtle)' }}; min-width: 16px; margin-top: 18px;"></div>
                @endif
            @endforeach
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
                                    <th style="text-align: center; width: 80px;" class="no-print">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->payments as $payment)
                                    <tr style="{{ $payment->isVoided() ? 'opacity: 0.6; background-color: rgba(220, 38, 38, 0.03);' : '' }}">
                                        <td style="font-size: var(--text-xs); vertical-align: top;">
                                            <div>{{ $payment->payment_date->format('d-m-Y H:i') }}</div>
                                            @if ($payment->isVoided())
                                                <div style="font-size: 10px; color: var(--status-danger); margin-top: 2px;">
                                                    Void: {{ $payment->voided_at?->format('d-m-Y H:i') }}
                                                </div>
                                            @endif
                                        </td>
                                        <td style="vertical-align: top;">
                                            <div style="font-weight: var(--font-semibold);">
                                                {{ $payment->method->label() }}
                                            </div>
                                            @if ($payment->method === \App\Enums\PaymentMethod::Tunai && $payment->cash_tendered !== null)
                                                <div style="font-size: 10px; color: var(--text-muted); margin-top: 2px;">
                                                    Uang Diterima: {{ $payment->formatted_cash_tendered }}<br>
                                                    Kembali: {{ $payment->formatted_change_amount }}
                                                </div>
                                            @endif
                                            @if ($payment->notes)
                                                <div style="font-size: 10px; color: var(--text-muted); font-style: italic; margin-top: 2px;">
                                                    Catatan: {{ $payment->notes }}
                                                </div>
                                            @endif
                                        </td>
                                        <td style="font-size: var(--text-xs); color: var(--text-muted); vertical-align: top;">
                                            {{ $payment->reference ?: '-' }}
                                        </td>
                                        <td style="font-size: var(--text-xs); vertical-align: top;">
                                            <div>{{ $payment->recorder->name }}</div>
                                            @if ($payment->isVoided() && $payment->voider)
                                                <div style="font-size: 10px; color: var(--status-danger);">
                                                    Dibatalkan: {{ $payment->voider->name }}
                                                </div>
                                            @endif
                                        </td>
                                        <td style="text-align: right; font-weight: var(--font-bold); color: {{ $payment->isValid() ? 'var(--status-success)' : 'var(--status-danger)' }}; vertical-align: top; {{ $payment->isVoided() ? 'text-decoration: line-through;' : '' }}">
                                            {{ $payment->formatted_amount }}
                                        </td>
                                        <td style="text-align: center; vertical-align: top;">
                                            @if ($payment->isValid())
                                                <span class="badge badge-success" style="font-size: 10px;">Valid</span>
                                            @else
                                                <span class="badge badge-danger" style="font-size: 10px;" title="{{ $payment->void_reason }}">Dibatalkan</span>
                                                <div style="font-size: 10px; color: var(--status-danger); text-align: left; margin-top: 4px; max-width: 180px; word-break: break-word;">
                                                    <em>"{{ $payment->void_reason }}"</em>
                                                </div>
                                            @endif
                                        </td>
                                        <td style="text-align: center; vertical-align: top;" class="no-print">
                                            @if ($payment->isValid())
                                                @can('void', $payment)
                                                    <button type="button" class="btn btn-danger btn-sm" style="font-size: 11px; padding: 2px 8px;"
                                                            onclick="openVoidModal({{ $payment->id }}, '{{ $payment->formatted_amount }}')"
                                                            title="Batalkan pembayaran ini (khusus Administrator)">
                                                        Void
                                                    </button>
                                                @else
                                                    <span style="color: var(--text-muted); font-size: 11px;">-</span>
                                                @endcan
                                            @else
                                                <span style="color: var(--text-muted); font-size: 10px;">Voided</span>
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

            <!-- 4. Formulir Pencatatan Pembayaran Manual -->
            @can('create', [\App\Models\Payment::class, $order])
                <div class="glass-card no-print" style="padding: var(--space-5);">
                    <div style="margin-bottom: var(--space-4); border-bottom: 1px solid var(--border-subtle); padding-bottom: var(--space-3);">
                        <h2 style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin: 0; display: flex; align-items: center; gap: var(--space-2);">
                            <x-icon name="plus" size="18" class="icon-primary" />
                            <span>Pencatatan Pembayaran Masuk</span>
                        </h2>
                        <p style="color: var(--text-muted); font-size: var(--text-xs); margin-top: var(--space-1);">
                            Sisa tagihan yang harus dibayar: <strong style="color: var(--status-danger); font-size: var(--text-sm);">Rp {{ number_format($order->remaining_balance, 0, ',', '.') }}</strong>.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('orders.payments.store', $order) }}" id="record-payment-form" data-prevent-double-submit>
                        @csrf

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--space-4);">
                            <!-- Nominal Pembayaran -->
                            <div class="form-group">
                                <label for="payment-amount" class="form-label required">
                                    Nominal Pembayaran (Rp)
                                </label>
                                <input type="text" name="amount" id="payment-amount" class="form-control" required
                                       placeholder="0" value="{{ old('amount', (int)$order->remaining_balance) }}"
                                       oninput="formatRupiahInput(this); updateChangeCalculator();">
                                <!-- Tombol Cepat Pelunasan & 50% -->
                                <div style="display: flex; gap: var(--space-2); margin-top: var(--space-2);">
                                    <button type="button" class="btn btn-secondary btn-sm" style="font-size: 11px; padding: 2px 8px;"
                                            onclick="setPaymentAmount({{ (int)$order->remaining_balance }})">
                                        Lunasi (Rp {{ number_format($order->remaining_balance, 0, ',', '.') }})
                                    </button>
                                    @if ($order->remaining_balance > 1000)
                                        <button type="button" class="btn btn-secondary btn-sm" style="font-size: 11px; padding: 2px 8px;"
                                                onclick="setPaymentAmount({{ (int)round($order->remaining_balance / 2) }})">
                                            50% (Rp {{ number_format(round($order->remaining_balance / 2), 0, ',', '.') }})
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Metode Pembayaran -->
                            <div class="form-group">
                                <label for="payment-method" class="form-label required">
                                    Metode Pembayaran
                                </label>
                                <select name="method" id="payment-method" class="form-control" required onchange="handlePaymentMethodChange(this.value)">
                                    @foreach ($paymentMethods ?? \App\Enums\PaymentMethod::cases() as $m)
                                        <option value="{{ $m->value }}" {{ old('method', 'tunai') === $m->value ? 'selected' : '' }}>
                                            {{ $m->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- No Referensi Opsional -->
                            <div class="form-group">
                                <label for="payment-reference" class="form-label">
                                    Nomor Referensi (Opsional)
                                </label>
                                <input type="text" name="reference" id="payment-reference" class="form-control"
                                       placeholder="Contoh: TF-829103, QRIS-1290" value="{{ old('reference') }}">
                            </div>

                            <!-- Tanggal Pembayaran (Khusus Administrator) -->
                            @if (auth()->user()->isAdmin())
                                <div class="form-group">
                                    <label for="payment-date" class="form-label">
                                        Tanggal Pembayaran (Admin Backdate)
                                    </label>
                                    <input type="datetime-local" name="payment_date" id="payment-date" class="form-control"
                                           value="{{ old('payment_date', now()->format('Y-m-d\TH:i')) }}"
                                           max="{{ now()->format('Y-m-d\TH:i') }}">
                                    <small style="font-size: 10px; color: var(--text-muted); display: block; margin-top: 2px;">
                                        Diisi lampau hanya jika rekonsiliasi transfer bank.
                                    </small>
                                </div>
                            @endif
                        </div>

                        <!-- Panel Kalkulator Kembalian Tunai -->
                        <div id="cash-calculator-panel" style="background-color: var(--primary-subtle); border-radius: var(--radius-md); padding: var(--space-4); margin-top: var(--space-4); border: 1px solid var(--border-subtle); display: block;">
                            <div style="font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--primary-dark); text-transform: uppercase; margin-bottom: var(--space-2);">
                                Kalkulator Uang Tunai & Kembalian
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4); align-items: start;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="payment-cash-tendered" class="form-label" style="font-size: 11px;">
                                        Uang Fisik Diterima Kasir (Rp)
                                    </label>
                                    <input type="text" name="cash_tendered" id="payment-cash-tendered" class="form-control"
                                           placeholder="Contoh: 50.000, 100.000" value="{{ old('cash_tendered') }}"
                                           oninput="formatRupiahInput(this); updateChangeCalculator();">
                                </div>
                                <div>
                                    <div style="font-size: 11px; font-weight: var(--font-semibold); color: var(--text-muted); margin-bottom: 4px;">
                                        Uang Kembalian Pelanggan:
                                    </div>
                                    <div id="payment-change-display" style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--status-success);">
                                        Rp 0
                                    </div>
                                    <small id="payment-change-note" style="font-size: 10px; color: var(--text-muted); display: block; margin-top: 2px;">
                                        Uang kembalian dihitung otomatis.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Catatan Pembayaran -->
                        <div class="form-group" style="margin-top: var(--space-4);">
                            <label for="payment-notes" class="form-label" id="payment-notes-label">
                                Catatan / Keterangan Pembayaran (Opsional)
                            </label>
                            <textarea name="notes" id="payment-notes" class="form-control" rows="2"
                                      placeholder="Catatan tambahan (wajib jika metode Lainnya atau rekonsiliasi backdate)...">{{ old('notes') }}</textarea>
                        </div>

                        <!-- Konfirmasi Penerimaan Dana -->
                        <div style="margin-top: var(--space-4); background: rgba(37, 99, 235, 0.05); padding: var(--space-3) var(--space-4); border-radius: var(--radius-sm); border-left: 3px solid var(--primary);">
                            <label style="display: flex; align-items: center; gap: var(--space-2); cursor: pointer; font-size: var(--text-xs); color: var(--text-main); font-weight: var(--font-semibold); margin: 0;">
                                <input type="checkbox" name="confirm_received" value="1" required style="width: 16px; height: 16px; accent-color: var(--primary);">
                                <span>Saya mengonfirmasi bahwa dana fisik tunai / mutasi transfer / QRIS telah diterima dan diverifikasi sah.</span>
                            </label>
                        </div>

                        <!-- Tombol Aksi Simpan -->
                        <div style="display: flex; justify-content: flex-end; margin-top: var(--space-4);">
                            <button type="submit" class="btn btn-primary" id="btn-submit-payment">
                                <x-icon name="check" size="16" />
                                <span>Simpan Catatan Pembayaran</span>
                            </button>
                        </div>
                    </form>
                </div>
            @endcan
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

                    <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(37, 99, 235, 0.05); padding: var(--space-2) var(--space-3); border-radius: var(--radius-sm);">
                        <div>
                            <span style="font-weight: var(--font-bold); color: var(--text-main); display: block;">Nilai Transaksi</span>
                            <span style="font-size: 10px; color: var(--text-muted);">(Total Tagihan Pesanan)</span>
                        </div>
                        <span style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--primary);">
                            Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}
                        </span>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(22, 163, 74, 0.05); padding: var(--space-2) var(--space-3); border-radius: var(--radius-sm);">
                        <div>
                            <span style="font-weight: var(--font-bold); color: var(--status-success); display: block;">Uang Diterima</span>
                            <span style="font-size: 10px; color: var(--text-muted);">(Pembayaran Valid Tercatat)</span>
                        </div>
                        <span style="font-weight: var(--font-extrabold); font-size: var(--text-base); color: var(--status-success);">
                            Rp {{ number_format($order->total_paid, 0, ',', '.') }}
                        </span>
                    </div>

                    <div style="border-top: 2px solid var(--border-glass); padding-top: var(--space-3); margin-top: var(--space-2); display: flex; justify-content: space-between; align-items: center; background: {{ $order->remaining_balance <= 0 ? 'rgba(22, 163, 74, 0.08)' : 'rgba(220, 38, 38, 0.05)' }}; padding: var(--space-3); border-radius: var(--radius-sm);">
                        <div>
                            <span style="font-weight: var(--font-bold); color: var(--text-main); display: block;">Saldo Tagihan</span>
                            <span style="font-size: 10px; color: var(--text-muted);">(Sisa Piutang)</span>
                        </div>
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
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-4); border-bottom: 1px solid var(--border-subtle); padding-bottom: var(--space-2); flex-wrap: wrap; gap: var(--space-2);">
                    <h3 style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-main); margin: 0;">
                        Riwayat Alur Status
                    </h3>
                    @can('correctStatus', $order)
                        <button type="button" class="btn btn-secondary btn-sm" onclick="openCorrectionModal()" title="Koreksi status cucian khusus Administrator" style="font-size: 11px;">
                            <x-icon name="edit" size="12" />
                            <span>Koreksi Status (Admin)</span>
                        </button>
                    @endcan
                </div>

                <div style="display: flex; flex-direction: column; gap: var(--space-4); position: relative;">
                    @foreach ($order->statusHistories as $hist)
                        <div style="display: flex; gap: var(--space-3); align-items: flex-start;">
                            <!-- Simpul Titik Indikator -->
                            <div style="width: 10px; height: 10px; border-radius: var(--radius-full); background-color: {{ $hist->is_correction ? 'var(--status-warning)' : 'var(--primary)' }}; margin-top: 5px; flex-shrink: 0; box-shadow: 0 0 0 3px {{ $hist->is_correction ? 'rgba(217, 119, 6, 0.15)' : 'var(--primary-subtle)' }};"></div>

                            <div style="display: flex; flex-direction: column; gap: 4px; width: 100%;">
                                <!-- Transisi Status Badge -->
                                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: var(--space-2);">
                                    <div style="display: flex; align-items: center; gap: var(--space-1); flex-wrap: wrap;">
                                        @if ($hist->is_initial)
                                            <span class="badge badge-neutral" style="font-size: 10px;">Status Awal</span>
                                            <span style="font-size: 11px; color: var(--text-muted);">&rarr;</span>
                                            <x-badge-status :status="$hist->to_status" />
                                        @else
                                            <x-badge-status :status="$hist->from_status" />
                                            <span style="font-size: 11px; color: var(--text-muted);">&rarr;</span>
                                            <x-badge-status :status="$hist->to_status" />
                                        @endif

                                        @if ($hist->is_correction)
                                            <span class="badge badge-warning" style="font-size: 10px; font-weight: var(--font-bold);" title="Status dikoreksi oleh Administrator">
                                                Koreksi Admin
                                            </span>
                                        @endif
                                    </div>

                                    <span style="font-size: 11px; color: var(--text-muted);">
                                        {{ $hist->formatted_created_at }}
                                    </span>
                                </div>

                                <!-- Aktor Petugas -->
                                <div style="font-size: var(--text-xs); color: var(--text-main);">
                                    Oleh: <strong>{{ $hist->changer->name }}</strong>
                                    <span style="color: var(--text-muted); font-size: 11px;">({{ $hist->changer->role->label() }})</span>
                                </div>

                                <!-- Catatan Operasional / Alasan Koreksi -->
                                @if ($hist->note)
                                    <div style="font-size: 11px; color: var(--text-main); background: {{ $hist->is_correction ? 'rgba(217, 119, 6, 0.08)' : 'rgba(0,0,0,0.03)' }}; border-left: 3px solid {{ $hist->is_correction ? 'var(--status-warning)' : 'var(--primary)' }}; padding: 4px 8px; border-radius: 0 var(--radius-sm) var(--radius-sm) 0; margin-top: 2px;">
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

<!-- Modal Konfirmasi Perubahan Status Cucian -->
<div class="modal-backdrop" id="status-modal" role="dialog" aria-modal="true" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-icon-wrap modal-icon-primary">
                <x-icon name="check" size="22" />
            </div>
            <div>
                <h3 class="modal-title" id="status-modal-title">Perbarui Status Cucian</h3>
                <p class="modal-desc" id="status-modal-desc">Konfirmasi pembaruan status pengerjaan pesanan ini.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('orders.update-status', $order) }}" id="status-update-form" data-prevent-double-submit>
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" id="modal-target-status" value="">

            <div style="padding: 0 var(--space-6) var(--space-4);">
                <!-- Peringatan Pembayaran Belum Lunas jika Serah Terima / Siap Diambil -->
                <div id="unpaid-warning-box" class="alert alert-warning" style="display: none; margin-bottom: var(--space-4); font-size: var(--text-xs);">
                    <div style="display: flex; align-items: flex-start; gap: var(--space-2);">
                        <x-icon name="alert-triangle" size="18" style="flex-shrink: 0; margin-top: 2px;" />
                        <div>
                            <strong>Perhatian: Tagihan Belum Lunas!</strong><br>
                            Sisa tagihan pesanan ini adalah <strong id="unpaid-balance-text"></strong>. Pastikan pembayaran telah diterima atau dicatat sebelum serah terima kepada pelanggan.
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="status-note-input" class="form-label">
                        Catatan Operasional (Opsional)
                    </label>
                    <textarea name="note" id="status-note-input" class="form-control" rows="2" placeholder="Contoh: Mesin cuci no. 2, rak C4, telah selesai diperiksa rapi..."></textarea>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeStatusModal()">
                    Batal
                </button>
                <button type="submit" class="btn btn-primary" id="status-submit-btn">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Pembatalan Pesanan -->
<div class="modal-backdrop" id="cancel-modal" role="dialog" aria-modal="true" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-icon-wrap modal-icon-danger">
                <x-icon name="alert-triangle" size="22" />
            </div>
            <div>
                <h3 class="modal-title">Batalkan Pesanan {{ $order->order_number }}</h3>
                <p class="modal-desc">Pesanan yang dibatalkan tidak dapat diproses kembali. Jejak transaksi tetap disimpan untuk audit operasional.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('orders.update-status', $order) }}" id="cancel-order-form" data-prevent-double-submit>
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="{{ \App\Enums\LaundryStatus::Dibatalkan->value }}">

            <div style="padding: 0 var(--space-6) var(--space-4);">
                <div class="form-group">
                    <label for="cancellation-reason-input" class="form-label required">
                        Alasan Pembatalan (Wajib Diisi)
                    </label>
                    <textarea name="cancellation_reason" id="cancellation-reason-input" class="form-control" rows="3" required placeholder="Tuliskan alasan pembatalan secara rinci (misal: Pelanggan membatalkan pesanan sebelum dicuci, kesalahan input transaksi kasir)..."></textarea>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeCancelModal()">
                    Tutup
                </button>
                <button type="submit" class="btn btn-danger">
                    Ya, Batalkan Pesanan
                </button>
            </div>
        </form>
    </div>
</div>

@can('correctStatus', $order)
<!-- Modal Konfirmasi Koreksi Status (Khusus Administrator) -->
<div class="modal-backdrop" id="correction-modal" role="dialog" aria-modal="true" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-icon-wrap modal-icon-warning">
                <x-icon name="settings" size="22" />
            </div>
            <div>
                <h3 class="modal-title">Koreksi Status Cucian (Admin)</h3>
                <p class="modal-desc">Koreksi status digunakan bila terjadi kekeliruan input kasir. Seluruh tindakan dicatat pada audit log sistem.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('orders.correct-status', $order) }}" id="status-correction-form" data-prevent-double-submit>
            @csrf
            @method('PATCH')

            <div style="padding: 0 var(--space-6) var(--space-4); display: flex; flex-direction: column; gap: var(--space-4);">
                <div class="form-group">
                    <label for="correction-target-status" class="form-label required">
                        Status Target Koreksi
                    </label>
                    <select name="status" id="correction-target-status" class="form-control" required>
                        <option value="">-- Pilih Status Target --</option>
                        @foreach (\App\Enums\LaundryStatus::cases() as $st)
                            @if ($st !== $order->laundry_status && $st !== \App\Enums\LaundryStatus::Dibatalkan)
                                <option value="{{ $st->value }}">
                                    {{ $st->label() }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <small style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block;">
                        Status saat ini: <strong>{{ $order->laundry_status->label() }}</strong>.
                    </small>
                </div>

                <div class="form-group">
                    <label for="correction-reason-input" class="form-label required">
                        Alasan Koreksi Status (Wajib Diisi, Minimal 5 Karakter)
                    </label>
                    <textarea name="reason" id="correction-reason-input" class="form-control" rows="3" required placeholder="Contoh: Kesalahan klik status oleh kasir, pakaian sebenarnya belum selesai disetrika..."></textarea>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeCorrectionModal()">
                    Batal
                </button>
                <button type="submit" class="btn btn-warning">
                    Simpan Koreksi Status
                </button>
            </div>
        </form>
    </div>
</div>
@endcan

<!-- Modal Konfirmasi Void Pembayaran -->
<div class="modal-backdrop" id="void-modal" role="dialog" aria-modal="true" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-icon-wrap modal-icon-danger">
                <x-icon name="alert-triangle" size="22" />
            </div>
            <div>
                <h3 class="modal-title">Batalkan (Void) Pembayaran</h3>
                <p class="modal-desc" id="void-modal-desc">Pembatalan pembayaran akan mengembalikan saldo tagihan pesanan.</p>
            </div>
        </div>

        <form method="POST" action="" id="void-payment-form" data-prevent-double-submit>
            @csrf
            @method('PATCH')

            <div style="padding: 0 var(--space-6) var(--space-4);">
                <div class="alert alert-warning" style="font-size: var(--text-xs); margin-bottom: var(--space-4);">
                    <div style="display: flex; align-items: flex-start; gap: var(--space-2);">
                        <x-icon name="alert-triangle" size="16" style="flex-shrink: 0; margin-top: 1px;" />
                        <div>
                            <strong>Peringatan Jejak Audit:</strong> Riwayat pembayaran tidak dihapus fisik, melainkan ditandai "Void" dengan nama Administrator dan alasan pembatalan. Sisa tagihan akan disesuaikan kembali.
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="void-reason-input" class="form-label required">
                        Alasan Pembatalan (Wajib Diisi, Minimal 10 Karakter)
                    </label>
                    <textarea name="reason" id="void-reason-input" class="form-control" rows="3" required
                              placeholder="Contoh: Kesalahan input kasir nominal Rp50.000 menjadi Rp500.000, bukti transfer tidak masuk..."></textarea>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeVoidModal()">
                    Batal
                </button>
                <button type="submit" class="btn btn-danger">
                    Ya, Batalkan Pembayaran (Void)
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openVoidModal(paymentId, amountFormatted) {
    const form = document.getElementById('void-payment-form');
    if (form) {
        form.action = '/payments/' + paymentId + '/void';
    }
    const desc = document.getElementById('void-modal-desc');
    if (desc) {
        desc.textContent = 'Konfirmasi pembatalan pembayaran #' + paymentId + ' sebesar ' + amountFormatted + '.';
    }
    const modal = document.getElementById('void-modal');
    if (modal) modal.style.display = 'flex';
}

function closeVoidModal() {
    const modal = document.getElementById('void-modal');
    if (modal) modal.style.display = 'none';
}

function setPaymentAmount(val) {
    const el = document.getElementById('payment-amount');
    if (el) {
        el.value = Number(val).toLocaleString('id-ID');
        updateChangeCalculator();
    }
}

function handlePaymentMethodChange(val) {
    const panel = document.getElementById('cash-calculator-panel');
    const notesLabel = document.getElementById('payment-notes-label');

    if (panel) {
        panel.style.display = (val === 'tunai') ? 'block' : 'none';
    }

    if (notesLabel) {
        if (val === 'lainnya') {
            notesLabel.textContent = 'Catatan / Keterangan Pembayaran (Wajib Diisi)';
            notesLabel.classList.add('required');
        } else {
            notesLabel.textContent = 'Catatan / Keterangan Pembayaran (Opsional)';
            notesLabel.classList.remove('required');
        }
    }
    updateChangeCalculator();
}

function updateChangeCalculator() {
    const amountEl = document.getElementById('payment-amount');
    const tenderedEl = document.getElementById('payment-cash-tendered');
    const changeDisplay = document.getElementById('payment-change-display');
    const changeNote = document.getElementById('payment-change-note');

    if (!amountEl || !tenderedEl || !changeDisplay) return;

    const amount = parseInt(amountEl.value.replace(/[^0-9]/g, '')) || 0;
    const tendered = parseInt(tenderedEl.value.replace(/[^0-9]/g, '')) || 0;

    if (tendered > 0) {
        const diff = tendered - amount;
        if (diff >= 0) {
            changeDisplay.textContent = 'Rp ' + diff.toLocaleString('id-ID');
            changeDisplay.style.color = 'var(--status-success)';
            if (changeNote) changeNote.textContent = 'Uang kembalian yang harus diserahkan ke pelanggan.';
        } else {
            changeDisplay.textContent = 'Kurang Rp ' + Math.abs(diff).toLocaleString('id-ID');
            changeDisplay.style.color = 'var(--status-danger)';
            if (changeNote) changeNote.textContent = 'Uang tunai fisik diterima kurang dari nominal tagihan.';
        }
    } else {
        changeDisplay.textContent = 'Rp 0';
        changeDisplay.style.color = 'var(--status-success)';
        if (changeNote) changeNote.textContent = 'Uang kembalian dihitung otomatis.';
    }
}

function formatRupiahInput(input) {
    let value = input.value.replace(/[^0-9]/g, '');
    if (value) {
        input.value = Number(value).toLocaleString('id-ID');
    } else {
        input.value = '';
    }
}

function openCorrectionModal() {
    const modal = document.getElementById('correction-modal');
    if (modal) modal.style.display = 'flex';
}

function closeCorrectionModal() {
    const modal = document.getElementById('correction-modal');
    if (modal) modal.style.display = 'none';
}

function openStatusModal(statusValue, statusLabel, remainingBalance) {
    document.getElementById('modal-target-status').value = statusValue;
    document.getElementById('status-modal-title').textContent = 'Lanjut ke: ' + statusLabel;
    document.getElementById('status-modal-desc').textContent = 'Perbarui status pesanan {{ $order->order_number }} menjadi ' + statusLabel + '.';

    const warningBox = document.getElementById('unpaid-warning-box');
    if ((statusValue === 'siap_diambil' || statusValue === 'selesai') && remainingBalance > 0) {
        document.getElementById('unpaid-balance-text').textContent = 'Rp ' + Number(remainingBalance).toLocaleString('id-ID');
        warningBox.style.display = 'block';
    } else {
        warningBox.style.display = 'none';
    }

    document.getElementById('status-modal').style.display = 'flex';
}

function closeStatusModal() {
    document.getElementById('status-modal').style.display = 'none';
}

function openCancelModal() {
    document.getElementById('cancel-modal').style.display = 'flex';
}

function closeCancelModal() {
    document.getElementById('cancel-modal').style.display = 'none';
}

function toggleStatusDropdown(event) {
    event.stopPropagation();
    const dropdown = document.getElementById('status-alt-dropdown');
    if (dropdown) {
        dropdown.style.display = (dropdown.style.display === 'none' || dropdown.style.display === '') ? 'block' : 'none';
    }
}

function closeStatusDropdown() {
    const dropdown = document.getElementById('status-alt-dropdown');
    if (dropdown) dropdown.style.display = 'none';
}

document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('status-alt-dropdown');
    if (dropdown && !e.target.closest('#status-alt-dropdown') && !e.target.closest('button[onclick*="toggleStatusDropdown"]')) {
        dropdown.style.display = 'none';
    }
});
</script>

<style>
@media (max-width: 900px) {
    .order-detail-layout {
        grid-template-columns: 1fr !important;
    }
}
</style>
@endsection

