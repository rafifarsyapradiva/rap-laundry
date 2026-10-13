@extends('layouts.app')

@section('title', 'Daftar Pesanan Laundry')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6);">
    <!-- Header Halaman -->
    <div class="glass-card" style="padding: var(--space-6);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-4);">
            <div>
                <h2 style="font-size: var(--text-2xl); font-weight: var(--font-bold); color: var(--text-main); margin-bottom: var(--space-1);">
                    Daftar Transaksi Pesanan
                </h2>
                <p style="color: var(--text-muted); font-size: var(--text-sm);">
                    Kelola dan telusuri seluruh riwayat pesanan cucian pelanggan &bull; Menampilkan {{ $orders->total() }} total pesanan
                </p>
            </div>
            <div style="display: flex; gap: var(--space-2); flex-wrap: wrap;" class="no-print">
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.reports.index') }}" class="btn btn-secondary">
                        <x-icon name="reports" size="16" />
                        <span>Laporan Keuangan</span>
                    </a>
                @endif
                <a href="{{ route('orders.create') }}" class="btn btn-primary">
                    <x-icon name="plus" size="16" />
                    <span>Pesanan Baru</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Bar Komprehensif (Task 10.2: Pencarian & Multi-Filter) -->
    <div class="glass-card" style="padding: var(--space-5);">
        <form method="GET" action="{{ route('orders.index') }}" id="order-filter-form">
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
                            placeholder="Nomor nota, nama, telepon..."
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
                    <label class="form-label" for="filter-date-from">Dari Tanggal Masuk</label>
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
                    <label class="form-label" for="filter-date-to">Sampai Tanggal Masuk</label>
                    <input
                        type="date"
                        id="filter-date-to"
                        name="date_to"
                        class="form-control"
                        value="{{ request('date_to') }}"
                    >
                </div>

                <!-- Filter Status Cucian -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="filter-laundry-status">Status Cucian</label>
                    <select id="filter-laundry-status" name="laundry_status" class="form-control">
                        <option value="">Semua Status Cucian</option>
                        @foreach($laundryStatuses as $status)
                            <option value="{{ $status->value }}" {{ request('laundry_status') === $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Status Pembayaran -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="filter-payment-status">Status Pembayaran</label>
                    <select id="filter-payment-status" name="payment_status" class="form-control">
                        <option value="">Semua Status Bayar</option>
                        @foreach($paymentStatuses as $pStatus)
                            <option value="{{ $pStatus->value }}" {{ request('payment_status') === $pStatus->value ? 'selected' : '' }}>
                                {{ $pStatus->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Petugas Kasir -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="filter-user-id">Petugas Kasir</label>
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
                    @if(request()->hasAny(['q', 'date_from', 'date_to', 'laundry_status', 'payment_status', 'user_id']) && (request('q') || request('date_from') || request('date_to') || request('laundry_status') || request('payment_status') || request('user_id')))
                        <span class="badge badge-primary" style="margin-right: 0.25rem;">Filter Aktif</span>
                        Menemukan <strong>{{ $orders->total() }}</strong> transaksi yang sesuai kriteria.
                    @else
                        Menampilkan seluruh riwayat pesanan (default: terbaru).
                    @endif
                </div>

                <div style="display: flex; gap: var(--space-2);">
                    @if(request()->hasAny(['q', 'date_from', 'date_to', 'laundry_status', 'payment_status', 'user_id']) && (request('q') || request('date_from') || request('date_to') || request('laundry_status') || request('payment_status') || request('user_id')))
                        <a href="{{ route('orders.index') }}" class="btn btn-secondary btn-sm" title="Hapus semua filter">
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

    <!-- Tabel Data Transaksi Pesanan (Task 10.2: Pagination & Efisiensi Query) -->
    @if($orders->isNotEmpty())
        <x-table :headers="['Nomor Nota', 'Pelanggan', 'Tanggal Masuk', 'Status Cucian', 'Status Pembayaran', 'Total Tagihan', 'Sisa Tagihan', 'Petugas', 'Aksi']">
            @foreach($orders as $order)
                <tr>
                    <td style="white-space: nowrap;">
                        <a href="{{ route('orders.show', $order) }}" style="font-weight: var(--font-bold); color: var(--primary, #2563EB); text-decoration: none;">
                            {{ $order->order_number }}
                        </a>
                    </td>
                    <td>
                        <strong style="color: var(--text-main); display: block;">{{ $order->customer->name ?? '-' }}</strong>
                        <span style="font-size: var(--text-xs); color: var(--text-muted); display: block;">
                            {{ $order->customer->phone ?? '' }}
                        </span>
                    </td>
                    <td style="white-space: nowrap;">
                        <div style="font-size: var(--text-sm); color: var(--text-main);">
                            {{ $order->received_at->format('d/m/Y') }}
                        </div>
                        <div style="font-size: var(--text-xs); color: var(--text-muted);">
                            {{ $order->received_at->format('H:i') }} WIB
                        </div>
                    </td>
                    <td style="white-space: nowrap;">
                        <x-badge-status :status="$order->laundry_status" />
                    </td>
                    <td style="white-space: nowrap;">
                        <x-badge-status :status="$order->payment_status" />
                    </td>
                    <td style="text-align: right; font-weight: 600; color: var(--text-main); white-space: nowrap;">
                        {{ $order->formatted_total_amount }}
                    </td>
                    <td style="text-align: right; font-weight: 600; white-space: nowrap; color: {{ $order->remaining_balance > 0 ? 'var(--color-danger, #DC2626)' : 'var(--color-success, #16A34A)' }};">
                        {{ $order->formatted_remaining_balance }}
                    </td>
                    <td style="white-space: nowrap; font-size: var(--text-xs); color: var(--text-muted);">
                        {{ $order->creator->name ?? '-' }}
                    </td>
                    <td style="white-space: nowrap; text-align: center;">
                        <div style="display: flex; gap: var(--space-2); justify-content: center; align-items: center;">
                            <a href="{{ route('orders.show', $order) }}" class="btn btn-secondary btn-sm" title="Rincian Transaksi">
                                <x-icon name="eye" size="14" />
                                <span>Detail</span>
                            </a>
                            <a href="{{ route('orders.print', $order) }}" class="btn btn-outline btn-sm" title="Cetak Nota">
                                <x-icon name="print" size="14" />
                            </a>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-table>

        <!-- Pagination Bar (Pertahankan Query String) -->
        <x-pagination :paginator="$orders" />
    @else
        <div class="glass-card" style="padding: var(--space-8);">
            <x-empty-state
                icon="orders"
                title="Tidak Ada Pesanan Ditemukan"
                description="{{ request()->hasAny(['q', 'date_from', 'date_to', 'laundry_status', 'payment_status', 'user_id']) ? 'Tidak ada transaksi yang cocok dengan kriteria filter atau pencarian Anda. Silakan coba sesuaikan kata kunci atau reset filter.' : 'Belum ada transaksi cucian yang tercatat di database.' }}"
                actionText="Buat Pesanan Baru"
                :actionUrl="route('orders.create')"
            />
            @if(request()->hasAny(['q', 'date_from', 'date_to', 'laundry_status', 'payment_status', 'user_id']))
                <div style="text-align: center; margin-top: var(--space-4);">
                    <a href="{{ route('orders.index') }}" class="btn btn-secondary">
                        <x-icon name="x" size="16" />
                        <span>Hapus Filter & Tampilkan Semua</span>
                    </a>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
