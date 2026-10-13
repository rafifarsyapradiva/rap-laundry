@extends('layouts.app')

@section('title', 'Detail Pelanggan — ' . $customer->name)

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6);">
    <!-- Navigasi & Aksi Cepat Halaman -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-4);">
        <a href="{{ route('customers.index') }}" class="btn btn-secondary btn-sm">
            <x-icon name="arrow-left" size="14" />
            <span>Kembali ke Daftar Pelanggan</span>
        </a>

        <div style="display: flex; gap: var(--space-2); flex-wrap: wrap; align-items: center;">
            @if (! $customer->trashed())
                <!-- Ubah Data -->
                <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-secondary btn-sm">
                    <x-icon name="edit" size="14" />
                    <span>Ubah Data</span>
                </a>

                <!-- Toggle Aktif/Nonaktif -->
                <form action="{{ route('customers.toggle-status', $customer->id) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-outline btn-sm">
                        <span>{{ $customer->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</span>
                    </button>
                </form>

                <!-- Tombol Arsip -->
                <button
                    type="button"
                    class="btn btn-outline btn-sm"
                    style="color: var(--status-danger); border-color: var(--status-danger-border);"
                    onclick="window.LaundryDesk.openConfirmModal({
                        action: '{{ route('customers.destroy', $customer->id) }}',
                        method: 'DELETE',
                        title: 'Arsipkan Pelanggan',
                        message: 'Apakah Anda yakin ingin mengarsipkan {{ $customer->name }}? Pelanggan tidak akan dapat dipilih saat pembuatan pesanan baru.',
                        confirmText: 'Ya, Arsipkan',
                        confirmClass: 'btn-danger'
                    })"
                >
                    <x-icon name="trash" size="14" />
                    <span>Arsipkan</span>
                </button>
            @else
                <!-- Pulihkan dari Arsip -->
                <form action="{{ route('customers.restore', $customer->id) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">
                        <x-icon name="check" size="14" />
                        <span>Pulihkan Pelanggan</span>
                    </button>
                </form>

                @if (auth()->user()?->isAdmin() && $orders->total() == 0)
                    <!-- Hapus Permanen (Jika tanpa riwayat) -->
                    <button
                        type="button"
                        class="btn btn-danger btn-sm"
                        onclick="window.LaundryDesk.openConfirmModal({
                            action: '{{ route('customers.force-delete', $customer->id) }}',
                            method: 'DELETE',
                            title: 'Hapus Permanen',
                            message: 'Pelanggan {{ $customer->name }} belum memiliki riwayat transaksi dan akan dihapus permanen.',
                            confirmText: 'Hapus Permanen',
                            confirmClass: 'btn-danger'
                        })"
                    >
                        <x-icon name="trash" size="14" />
                        <span>Hapus Permanen</span>
                    </button>
                @endif
            @endif
        </div>
    </div>

    <!-- Kartu Profil Pelanggan Glassmorphism -->
    <div class="glass-card" style="padding: var(--space-6);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: var(--space-4);">
            <div style="display: flex; gap: var(--space-4); align-items: center;">
                <div style="width: 4rem; height: 4rem; border-radius: var(--radius-full); background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: #fff; display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); font-weight: var(--font-bold); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);">
                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: var(--space-2); flex-wrap: wrap;">
                        <h2 style="font-size: var(--text-2xl); font-weight: var(--font-bold); color: var(--text-main); margin: 0;">
                            {{ $customer->name }}
                        </h2>
                        <span class="badge badge-primary" style="font-weight: var(--font-bold);">
                            {{ $customer->customer_code }}
                        </span>
                        @if ($customer->trashed())
                            <span class="badge badge-neutral">Terarsip</span>
                        @elseif ($customer->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-warning">Nonaktif</span>
                        @endif
                    </div>
                    <div style="display: flex; gap: var(--space-4); align-items: center; margin-top: var(--space-2); flex-wrap: wrap; font-size: var(--text-sm); color: var(--text-muted);">
                        <a href="tel:{{ $customer->phone }}" style="color: var(--primary-dark); text-decoration: none; font-weight: var(--font-semibold); display: inline-flex; align-items: center; gap: var(--space-1);">
                            <span>Telepon: {{ $customer->formatted_phone }}</span>
                        </a>
                        <span>&bull;</span>
                        <span>Terdaftar: {{ $customer->created_at->format('d-m-Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Informasi Kontak & Catatan -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--space-4); margin-top: var(--space-6); padding-top: var(--space-5); border-top: 1px solid var(--border-subtle);">
            <div>
                <span style="font-size: var(--text-xs); font-weight: var(--font-semibold); text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); display: block; margin-bottom: var(--space-1);">
                    Alamat Domisili
                </span>
                <p style="font-size: var(--text-sm); color: var(--text-main); margin: 0;">
                    {{ $customer->address ?: 'Belum ada data alamat domisili.' }}
                </p>
            </div>

            <div>
                <span style="font-size: var(--text-xs); font-weight: var(--font-semibold); text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); display: block; margin-bottom: var(--space-1);">
                    Catatan Khusus Pelanggan
                </span>
                <p style="font-size: var(--text-sm); color: var(--text-main); margin: 0; font-style: {{ $customer->notes ? 'normal' : 'italic' }};">
                    {{ $customer->notes ?: 'Tidak ada catatan khusus untuk pelanggan ini.' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Ringkasan Statistik Metrik Pelanggan -->
    <div class="grid grid-cols-4">
        <!-- Total Pesanan -->
        <x-stat-card
            title="Total Pesanan"
            :value="$metrics['total_orders']"
            hint="Seluruh riwayat cucian"
            icon="orders"
        />

        <!-- Pesanan Berjalan / Aktif -->
        <x-stat-card
            title="Pesanan Aktif"
            :value="$metrics['active_orders']"
            hint="Sedang dalam proses pengerjaan"
            icon="clock"
        />

        <!-- Total Transaksi -->
        <x-stat-card
            title="Total Akumulasi Biaya"
            :value="'Rp' . number_format((float) $metrics['total_spent'], 0, ',', '.')"
            hint="Nilai bruto transaksi selesai"
            icon="receipt"
        />

        <!-- Sisa Tagihan / Piutang -->
        <div class="glass-card stat-card">
            <div class="stat-header">
                <span class="stat-label">Sisa Tagihan Belum Lunas</span>
                <div class="stat-icon {{ (float) $metrics['outstanding_balance'] > 0 ? 'stat-icon-warning' : 'stat-icon-success' }}">
                    <x-icon name="payments" size="20" />
                </div>
            </div>
            <div class="stat-value" style="font-size: var(--text-2xl); color: {{ (float) $metrics['outstanding_balance'] > 0 ? 'var(--status-danger)' : 'var(--status-success)' }};">
                Rp{{ number_format((float) $metrics['outstanding_balance'], 0, ',', '.') }}
            </div>
            <span class="stat-hint">
                {{ (float) $metrics['outstanding_balance'] > 0 ? 'Ada tagihan yang belum dilunasi' : 'Semua pesanan lunas' }}
            </span>
        </div>
    </div>

    <!-- Riwayat Transaksi Cucian -->
    <div style="display: flex; flex-direction: column; gap: var(--space-4);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-3);">
            <div>
                <h3 style="font-size: var(--text-lg); font-weight: var(--font-bold); color: var(--text-main); margin: 0;">
                    Riwayat Transaksi Cucian
                </h3>
                <p style="font-size: var(--text-xs); color: var(--text-muted); margin: 0;">
                    Daftar seluruh pesanan cucian yang pernah dibuat untuk {{ $customer->name }}.
                </p>
            </div>
        </div>

        @if ($orders->isNotEmpty())
            <x-table :headers="['No. Pesanan', 'Tgl Masuk', 'Rincian Layanan', 'Total Tagihan', 'Status Cucian', 'Pembayaran', 'Aksi']">
                @foreach ($orders as $order)
                    <tr>
                        <td style="white-space: nowrap; font-weight: var(--font-bold); color: var(--primary);">
                            {{ $order->order_number }}
                        </td>
                        <td style="white-space: nowrap; font-size: var(--text-xs);">
                            {{ $order->received_at ? $order->received_at->format('d-m-Y H:i') : '-' }}
                        </td>
                        <td>
                            @if ($order->items->isNotEmpty())
                                <div style="display: flex; flex-direction: column; gap: 0.125rem;">
                                    @foreach ($order->items->take(2) as $item)
                                        <span style="font-size: var(--text-xs); color: var(--text-main);">
                                            {{ $item->service_name_snapshot }} ({{ $item->quantity }} {{ $item->billing_unit_snapshot instanceof \App\Enums\BillingUnit ? $item->billing_unit_snapshot->label() : ($item->billing_unit_snapshot ?? '') }})
                                        </span>
                                    @endforeach
                                    @if ($order->items->count() > 2)
                                        <span style="font-size: 0.6875rem; color: var(--text-muted);">
                                            +{{ $order->items->count() - 2 }} layanan lainnya
                                        </span>
                                    @endif
                                </div>
                            @else
                                <span style="color: var(--text-muted); font-size: var(--text-xs);">-</span>
                            @endif
                        </td>
                        <td style="white-space: nowrap; font-weight: var(--font-bold);">
                            Rp{{ number_format((float) $order->total_amount, 0, ',', '.') }}
                        </td>
                        <td style="white-space: nowrap;">
                            <x-badge-status :status="$order->laundry_status" />
                        </td>
                        <td style="white-space: nowrap;">
                            <x-badge-status :status="$order->payment_status" />
                        </td>
                        <td style="white-space: nowrap;">
                            @if (Route::has('orders.show'))
                                <a href="{{ route('orders.show', $order->id) }}" class="btn btn-secondary btn-sm" title="Lihat Nota & Detail Transaksi">
                                    <x-icon name="eye" size="14" />
                                    <span>Nota</span>
                                </a>
                            @else
                                <span class="badge badge-neutral" style="font-size: var(--text-xs);">Pesanan #{{ $order->id }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>

            <x-pagination :paginator="$orders" />
        @else
            <div class="glass-card" style="padding: var(--space-8);">
                <x-empty-state
                    icon="orders"
                    title="Belum Ada Riwayat Pesanan Cucian"
                    message="Pelanggan ini belum pernah melakukan transaksi pesanan cucian di LaundryDesk."
                />
            </div>
        @endif
    </div>
</div>
@endsection
