@extends('layouts.app')

@section('title', 'Riwayat Transaksi Pembayaran')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6);">
    <!-- Header Halaman & Tombol Aksi Cepat -->
    <div class="glass-card" style="padding: var(--space-6);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-4);">
            <div>
                <h2 style="font-size: var(--text-2xl); font-weight: var(--font-bold); color: var(--text-main); display: flex; align-items: center; gap: var(--space-2); margin-bottom: var(--space-1);">
                    <x-icon name="payments" size="24" class="icon-primary" />
                    <span>Riwayat Transaksi Pembayaran</span>
                </h2>
                <p style="color: var(--text-muted); font-size: var(--text-sm);">
                    Pantau arus kas masuk riil, distribusi kanal pembayaran, dan jejak audit pembatalan (void) transaksi &bull; Menampilkan {{ $payments->total() }} catatan
                </p>
            </div>
            <div style="display: flex; gap: var(--space-2); flex-wrap: wrap;" class="no-print">
                <a href="{{ route('orders.index', ['payment_status' => 'belum_dibayar']) }}" class="btn btn-outline" title="Telusuri pesanan yang belum dibayar untuk pelunasan kasir">
                    <x-icon name="orders" size="16" />
                    <span>Pesanan Belum Lunas</span>
                </a>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.reports.payments.export') }}" class="btn btn-secondary" title="Export rekapan riwayat pembayaran format CSV">
                        <x-icon name="receipt" size="16" />
                        <span>Export CSV</span>
                    </a>
                    <a href="{{ route('admin.reports.index') }}" class="btn btn-primary" title="Buka ringkasan analitik dan laporan keuangan usaha">
                        <x-icon name="reports" size="16" />
                        <span>Laporan Keuangan</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- 4 Kartu Metrik Ringkasan Finansial Pembayaran -->
    <div class="grid grid-cols-4" style="gap: var(--space-4);">
        <x-stat-card
            title="Total Uang Masuk (Valid)"
            :value="'Rp' . number_format($metrics['total_valid'], 0, ',', '.')"
            icon="payments"
            color="success"
            hint="Arus kas masuk sah sesuai kriteria filter"
        />
        <x-stat-card
            title="Penerimaan Tunai (Cash)"
            :value="'Rp' . number_format($metrics['cash_valid'], 0, ',', '.')"
            icon="receipt"
            color="primary"
            hint="Uang fisik yang diterima kasir di laci"
        />
        <x-stat-card
            title="Non-Tunai (Transfer/QRIS)"
            :value="'Rp' . number_format($metrics['non_cash_valid'], 0, ',', '.')"
            icon="dashboard"
            color="info"
            hint="Kanal transfer bank dan QRIS manual"
        />
        <x-stat-card
            title="Pembayaran Dibatalkan"
            :value="$metrics['voided_count'] . ' Transaksi'"
            icon="alert-triangle"
            color="danger"
            hint="Pembayaran di-void oleh Administrator"
        />
    </div>

    <!-- Filter Bar Komprehensif -->
    <div class="glass-card" style="padding: var(--space-5);">
        <form method="GET" action="{{ route('payments.index') }}" id="payment-filter-form">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-4); margin-bottom: var(--space-4);">
                <!-- Pencarian Kata Kunci -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="filter-q">Pencarian</label>
                    <div style="position: relative;">
                        <input
                            type="text"
                            id="filter-q"
                            name="q"
                            class="form-control"
                            placeholder="Nomor nota, nama, referensi..."
                            value="{{ request('q') }}"
                            style="padding-left: 2rem;"
                        >
                        <span style="position: absolute; left: 0.625rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;">
                            <x-icon name="search" size="14" />
                        </span>
                    </div>
                </div>

                <!-- Rentang Tanggal: Dari -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="filter-date-from">Dari Tanggal Bayar</label>
                    <input
                        type="date"
                        id="filter-date-from"
                        name="date_from"
                        class="form-control"
                        value="{{ request('date_from') }}"
                    >
                </div>

                <!-- Rentang Tanggal: Sampai -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="filter-date-to">Sampai Tanggal Bayar</label>
                    <input
                        type="date"
                        id="filter-date-to"
                        name="date_to"
                        class="form-control"
                        value="{{ request('date_to') }}"
                    >
                </div>

                <!-- Filter Kanal / Metode Pembayaran -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="filter-method">Metode Pembayaran</label>
                    <select id="filter-method" name="method" class="form-control">
                        <option value="">Semua Metode</option>
                        @foreach($methods as $method)
                            <option value="{{ $method->value }}" {{ request('method') === $method->value ? 'selected' : '' }}>
                                {{ $method->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Status Catatan Pembayaran -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="filter-status">Status Record</label>
                    <select id="filter-status" name="status" class="form-control">
                        <option value="">Semua Status</option>
                        @foreach($recordStatuses as $rStatus)
                            <option value="{{ $rStatus->value }}" {{ request('status') === $rStatus->value ? 'selected' : '' }}>
                                {{ $rStatus->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Petugas Kasir Pencatat -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="filter-user-id">Petugas Pencatat</label>
                    <select id="filter-user-id" name="user_id" class="form-control">
                        <option value="">Semua Petugas</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ (string) request('user_id') === (string) $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Tombol Aksi Filter -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-2); padding-top: var(--space-2); border-top: 1px solid var(--border-color, rgba(148, 163, 184, 0.2));">
                <div style="font-size: var(--text-xs); color: var(--text-muted);">
                    @if(request()->hasAny(['q', 'date_from', 'date_to', 'method', 'status', 'user_id']) && (request('q') || request('date_from') || request('date_to') || request('method') || request('status') || request('user_id')))
                        <span class="badge badge-primary" style="margin-right: 0.25rem;">Filter Aktif</span>
                        Menemukan <strong>{{ $payments->total() }}</strong> catatan pembayaran yang cocok.
                    @else
                        Menampilkan seluruh riwayat transaksi pembayaran kasir &bull; Urutan terbaru.
                    @endif
                </div>

                <div style="display: flex; gap: var(--space-2);">
                    @if(request()->hasAny(['q', 'date_from', 'date_to', 'method', 'status', 'user_id']) && (request('q') || request('date_from') || request('date_to') || request('method') || request('status') || request('user_id')))
                        <a href="{{ route('payments.index') }}" class="btn btn-secondary btn-sm" title="Hapus seluruh filter">
                            <x-icon name="x" size="14" />
                            <span>Reset Filter</span>
                        </a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">
                        <x-icon name="filter" size="14" />
                        <span>Terapkan Filter</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabel Data Transaksi Pembayaran -->
    @if($payments->isNotEmpty())
        <x-table :headers="['ID / Waktu Bayar', 'Nomor Nota', 'Pelanggan', 'Metode Bayar', 'Nominal Diterima', 'Petugas', 'Status', 'Aksi']">
            @foreach($payments as $payment)
                <tr style="{{ $payment->isVoided() ? 'opacity: 0.65; background-color: rgba(220, 38, 38, 0.03);' : '' }}">
                    <!-- Kolom 1: ID & Waktu -->
                    <td style="white-space: nowrap; vertical-align: top;">
                        <div style="font-weight: var(--font-bold); color: var(--text-main);">
                            #{{ $payment->id }}
                        </div>
                        <div style="font-size: var(--text-xs); color: var(--text-muted);">
                            {{ $payment->payment_date->format('d/m/Y') }}
                        </div>
                        <div style="font-size: 10px; color: var(--text-muted);">
                            {{ $payment->payment_date->format('H:i') }} WIB
                        </div>
                        @if($payment->isVoided())
                            <div style="font-size: 10px; color: var(--color-danger, #DC2626); margin-top: 2px;">
                                Void: {{ $payment->voided_at?->format('d/m/Y H:i') }}
                            </div>
                        @endif
                    </td>

                    <!-- Kolom 2: Nomor Nota Pesanan -->
                    <td style="white-space: nowrap; vertical-align: top;">
                        @if($payment->order)
                            <a href="{{ route('orders.show', $payment->order) }}" style="font-weight: var(--font-bold); color: var(--primary, #2563EB); text-decoration: none; display: block;">
                                {{ $payment->order->order_number }}
                            </a>
                            <div style="margin-top: 4px;">
                                <x-badge-status :status="$payment->order->laundry_status" />
                            </div>
                        @else
                            <span style="color: var(--text-muted);">-</span>
                        @endif
                    </td>

                    <!-- Kolom 3: Pelanggan -->
                    <td style="vertical-align: top;">
                        <strong style="color: var(--text-main); display: block;">
                            {{ $payment->order?->customer?->name ?? '-' }}
                        </strong>
                        <span style="font-size: var(--text-xs); color: var(--text-muted); display: block;">
                            {{ $payment->order?->customer?->phone ?? '' }}
                        </span>
                    </td>

                    <!-- Kolom 4: Metode Bayar & Rincian Kasir -->
                    <td style="vertical-align: top;">
                        <div style="font-weight: var(--font-semibold); color: var(--text-main);">
                            {{ $payment->method->label() }}
                        </div>
                        @if($payment->method === \App\Enums\PaymentMethod::Tunai && $payment->cash_tendered !== null)
                            <div style="font-size: 10px; color: var(--text-muted); margin-top: 2px; line-height: 1.3;">
                                Uang Diterima: {{ $payment->formatted_cash_tendered }}<br>
                                Kembalian: {{ $payment->formatted_change_amount }}
                            </div>
                        @endif
                        @if($payment->reference)
                            <div style="font-size: 10px; color: var(--text-muted); margin-top: 2px;">
                                <strong>Ref:</strong> {{ $payment->reference }}
                            </div>
                        @endif
                        @if($payment->notes)
                            <div style="font-size: 10px; color: var(--text-muted); font-style: italic; margin-top: 2px;">
                                "{{ $payment->notes }}"
                            </div>
                        @endif
                    </td>

                    <!-- Kolom 5: Nominal Pembayaran -->
                    <td style="text-align: right; vertical-align: top; white-space: nowrap;">
                        <div style="font-weight: var(--font-bold); font-size: var(--text-base); color: {{ $payment->isValid() ? 'var(--color-success, #16A34A)' : 'var(--color-danger, #DC2626)' }}; {{ $payment->isVoided() ? 'text-decoration: line-through;' : '' }}">
                            {{ $payment->formatted_amount }}
                        </div>
                    </td>

                    <!-- Kolom 6: Petugas Pencatat & Pembatal -->
                    <td style="white-space: nowrap; vertical-align: top; font-size: var(--text-xs);">
                        <div style="color: var(--text-main);">
                            {{ $payment->recorder->name ?? '-' }}
                        </div>
                        @if($payment->isVoided() && $payment->voider)
                            <div style="font-size: 10px; color: var(--color-danger, #DC2626); margin-top: 2px;">
                                Dibatalkan: {{ $payment->voider->name }}
                            </div>
                        @endif
                    </td>

                    <!-- Kolom 7: Status Record -->
                    <td style="text-align: center; vertical-align: top; white-space: nowrap;">
                        @if($payment->isValid())
                            <span class="badge badge-success" style="font-size: 10px;">Valid</span>
                        @else
                            <span class="badge badge-danger" style="font-size: 10px;" title="{{ $payment->void_reason }}">
                                Dibatalkan (Void)
                            </span>
                            @if($payment->void_reason)
                                <div style="font-size: 10px; color: var(--color-danger, #DC2626); text-align: left; margin-top: 4px; max-width: 170px; word-break: break-word;">
                                    <em>"{{ $payment->void_reason }}"</em>
                                </div>
                            @endif
                        @endif
                    </td>

                    <!-- Kolom 8: Aksi -->
                    <td style="white-space: nowrap; text-align: center; vertical-align: top;">
                        <div style="display: flex; gap: var(--space-2); justify-content: center; align-items: center;">
                            @if($payment->order)
                                <a href="{{ route('orders.show', $payment->order) }}" class="btn btn-secondary btn-sm" title="Lihat Rincian Pesanan">
                                    <x-icon name="eye" size="14" />
                                    <span>Detail</span>
                                </a>
                            @endif

                            @if(auth()->user()->isAdmin() && $payment->isValid())
                                <button
                                    type="button"
                                    class="btn btn-outline btn-sm"
                                    style="color: var(--color-danger, #DC2626); border-color: rgba(220, 38, 38, 0.4);"
                                    title="Batalkan (Void) pembayaran ini (khusus Administrator)"
                                    onclick="openVoidModal('{{ $payment->id }}', '{{ $payment->formatted_amount }}', '{{ $payment->order?->order_number ?? '' }}')"
                                >
                                    <x-icon name="trash" size="14" />
                                    <span>Void</span>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-table>

        <!-- Pagination Bar -->
        <x-pagination :paginator="$payments" />
    @else
        <div class="glass-card" style="padding: var(--space-8);">
            <x-empty-state
                icon="payments"
                title="Tidak Ada Data Pembayaran Ditemukan"
                description="{{ request()->hasAny(['q', 'date_from', 'date_to', 'method', 'status', 'user_id']) ? 'Tidak ada catatan pembayaran yang cocok dengan parameter filter pencarian Anda. Silakan coba sesuaikan kata kunci atau reset filter.' : 'Belum ada transaksi pembayaran yang tercatat pada sistem.' }}"
                actionText="Lihat Pesanan Cucian"
                :actionUrl="route('orders.index')"
            />
            @if(request()->hasAny(['q', 'date_from', 'date_to', 'method', 'status', 'user_id']))
                <div style="text-align: center; margin-top: var(--space-4);">
                    <a href="{{ route('payments.index') }}" class="btn btn-secondary">
                        <x-icon name="x" size="16" />
                        <span>Hapus Filter & Tampilkan Semua</span>
                    </a>
                </div>
            @endif
        </div>
    @endif
</div>

<!-- Modal Konfirmasi Void Pembayaran (Khusus Administrator) -->
@if(auth()->user()->isAdmin())
    <div class="modal-backdrop" id="void-modal" role="dialog" aria-modal="true" style="display: none;">
        <div class="modal-dialog">
            <div class="modal-header">
                <div class="modal-icon-wrap modal-icon-danger">
                    <x-icon name="alert-triangle" size="22" />
                </div>
                <div>
                    <h3 class="modal-title">Batalkan (Void) Pembayaran</h3>
                    <p class="modal-desc" id="void-modal-desc">
                        Pembatalan pembayaran akan mengembalikan saldo sisa tagihan pesanan terkait.
                    </p>
                </div>
            </div>

            <form method="POST" action="" id="void-payment-form" data-prevent-double-submit>
                @csrf
                @method('PATCH')
                <input type="hidden" name="redirect_to" value="payments">

                <div style="padding: 0 var(--space-6) var(--space-4);">
                    <div class="alert alert-warning" style="font-size: var(--text-xs); margin-bottom: var(--space-4);">
                        <div style="display: flex; align-items: flex-start; gap: var(--space-2);">
                            <x-icon name="alert-triangle" size="16" style="flex-shrink: 0; margin-top: 1px;" />
                            <div>
                                <strong>Peringatan Jejak Audit:</strong> Pembayaran tidak dihapus permanen, melainkan ditandai "Void" dengan jejak nama Administrator dan alasan wajib. Status pembayaran pesanan akan disinkronisasi kembali.
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="void-reason-input" class="form-label required">
                            Alasan Pembatalan (Wajib Diisi, Minimal 10 Karakter)
                        </label>
                        <textarea
                            name="reason"
                            id="void-reason-input"
                            class="form-control"
                            rows="3"
                            required
                            minlength="10"
                            placeholder="Contoh: Kesalahan input kasir nominal Rp50.000 menjadi Rp500.000, bukti transfer tidak masuk..."
                        ></textarea>
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
    function openVoidModal(paymentId, amountFormatted, orderNumber) {
        const form = document.getElementById('void-payment-form');
        if (form) {
            form.action = '/payments/' + paymentId + '/void';
        }
        const desc = document.getElementById('void-modal-desc');
        if (desc) {
            desc.textContent = 'Konfirmasi pembatalan pembayaran #' + paymentId + ' (' + amountFormatted + ') untuk pesanan ' + orderNumber + '.';
        }
        const reasonInput = document.getElementById('void-reason-input');
        if (reasonInput) {
            reasonInput.value = '';
        }
        const modal = document.getElementById('void-modal');
        if (modal) modal.style.display = 'flex';
    }

    function closeVoidModal() {
        const modal = document.getElementById('void-modal');
        if (modal) modal.style.display = 'none';
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeVoidModal();
        }
    });

    const voidModalBackdrop = document.getElementById('void-modal');
    if (voidModalBackdrop) {
        voidModalBackdrop.addEventListener('click', function(e) {
            if (e.target === this) {
                closeVoidModal();
            }
        });
    }
    </script>
@endif
@endsection
