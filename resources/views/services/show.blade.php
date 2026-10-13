@extends('layouts.app')

@section('title', 'Detail Layanan — ' . $service->name)

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6);">
    <!-- Header Navigasi & Aksi Cepat -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-4);">
        <a href="{{ route('services.index') }}" class="btn btn-secondary btn-sm">
            <x-icon name="arrow-left" size="14" />
            <span>Kembali ke Master Layanan</span>
        </a>

        @if (auth()->check() && auth()->user()->isAdmin())
            <div style="display: flex; gap: var(--space-2); flex-wrap: wrap; align-items: center;">
                @if (! $service->trashed())
                    <a href="{{ route('services.edit', $service->id) }}" class="btn btn-secondary btn-sm">
                        <x-icon name="edit" size="14" />
                        <span>Ubah Layanan & Tarif</span>
                    </a>

                    <!-- Toggle Aktif/Nonaktif -->
                    <form action="{{ route('services.toggle-status', $service->id) }}" method="POST" style="display: inline;">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-outline btn-sm">
                            <span>{{ $service->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</span>
                        </button>
                    </form>

                    <!-- Tombol Arsipkan -->
                    <button
                        type="button"
                        class="btn btn-outline btn-sm"
                        style="color: var(--status-danger); border-color: var(--status-danger-border);"
                        onclick="window.LaundryDesk.openConfirmModal({
                            action: '{{ route('services.destroy', $service->id) }}',
                            method: 'DELETE',
                            title: 'Arsipkan Layanan',
                            message: 'Apakah Anda yakin ingin mengarsipkan layanan \'{{ $service->name }}\'? Layanan tidak akan muncul saat kasir membuat pesanan baru.',
                            confirmText: 'Ya, Arsipkan',
                            confirmClass: 'btn-danger'
                        })"
                    >
                        <x-icon name="trash" size="14" />
                        <span>Arsipkan</span>
                    </button>
                @else
                    <!-- Pulihkan dari Arsip -->
                    <form action="{{ route('services.restore', $service->id) }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm">
                            <x-icon name="check" size="14" />
                            <span>Pulihkan Layanan</span>
                        </button>
                    </form>

                    @if ($recentOrderItems->total() == 0)
                        <!-- Hapus Permanen (Jika tanpa riwayat) -->
                        <button
                            type="button"
                            class="btn btn-danger btn-sm"
                            onclick="window.LaundryDesk.openConfirmModal({
                                action: '{{ route('services.force-delete', $service->id) }}',
                                method: 'DELETE',
                                title: 'Hapus Permanen',
                                message: 'Layanan \'{{ $service->name }}\' belum pernah digunakan dan akan dihapus permanen.',
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
        @endif
    </div>

    <!-- Kartu Spesifikasi Layanan Glassmorphism -->
    <div class="glass-card" style="padding: var(--space-6);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: var(--space-4);">
            <div style="display: flex; gap: var(--space-4); align-items: center;">
                <div style="width: 3.5rem; height: 3.5rem; border-radius: var(--radius-md); background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);">
                    <x-icon name="services" size="24" />
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: var(--space-2); flex-wrap: wrap;">
                        <h2 style="font-size: var(--text-2xl); font-weight: var(--font-bold); color: var(--text-main); margin: 0;">
                            {{ $service->name }}
                        </h2>
                        <span class="badge {{ $service->billing_unit?->value === 'kg' ? 'badge-primary' : ($service->billing_unit?->value === 'item' ? 'badge-info' : 'badge-warning') }}">
                            {{ $service->billing_unit?->label() ?? 'Satuan' }}
                        </span>
                        @if ($service->trashed())
                            <span class="badge badge-neutral">Terarsip</span>
                        @elseif ($service->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-warning">Nonaktif</span>
                        @endif
                    </div>
                    <div style="font-size: var(--text-lg); font-weight: var(--font-bold); color: var(--primary-dark); margin-top: var(--space-1);">
                        {{ $service->formatted_price }}
                    </div>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: var(--space-4); margin-top: var(--space-6); padding-top: var(--space-5); border-top: 1px solid var(--border-subtle);">
            <div>
                <span style="font-size: var(--text-xs); font-weight: var(--font-semibold); text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); display: block; margin-bottom: var(--space-1);">
                    Estimasi Durasi Pengerjaan
                </span>
                <p style="font-size: var(--text-sm); color: var(--text-main); margin: 0; font-weight: var(--font-semibold);">
                    {{ $service->estimated_duration_text }}
                </p>
            </div>

            <div>
                <span style="font-size: var(--text-xs); font-weight: var(--font-semibold); text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); display: block; margin-bottom: var(--space-1);">
                    Deskripsi & Alur Proses
                </span>
                <p style="font-size: var(--text-sm); color: var(--text-main); margin: 0; font-style: {{ $service->description ? 'normal' : 'italic' }};">
                    {{ $service->description ?: 'Tidak ada deskripsi khusus.' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Ringkasan Statistik Metrik Penggunaan Layanan -->
    <div class="grid grid-cols-3">
        <!-- Frekuensi Digunakan -->
        <x-stat-card
            title="Frekuensi Transaksi"
            :value="$metrics['usage_count'] . ' pesanan'"
            hint="Banyaknya nota yang memakai layanan ini"
            icon="orders"
        />

        <!-- Akumulasi Kuantitas Terjual -->
        <x-stat-card
            title="Total Kuantitas Diproses"
            :value="number_format((float) $metrics['total_quantity'], 2, ',', '.') . ' ' . ($service->billing_unit?->label() ?? '')"
            hint="Volume cucian yang pernah diproses"
            icon="dashboard"
        />

        <!-- Total Omzet Penjualan -->
        <x-stat-card
            title="Total Omzet Dihasilkan"
            :value="'Rp' . number_format((float) $metrics['total_revenue'], 0, ',', '.')"
            hint="Nilai bruto pendapatan dari layanan ini"
            icon="payments"
        />
    </div>

    <!-- Riwayat Transaksi Menggunakan Layanan Ini -->
    <div style="display: flex; flex-direction: column; gap: var(--space-4);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-3);">
            <div>
                <h3 style="font-size: var(--text-lg); font-weight: var(--font-bold); color: var(--text-main); margin: 0;">
                    Riwayat Transaksi Menggunakan Layanan Ini
                </h3>
                <p style="font-size: var(--text-xs); color: var(--text-muted); margin: 0;">
                    Daftar item transaksi yang mencatat snapshot nama dan tarif layanan ini.
                </p>
            </div>
        </div>

        @if ($recentOrderItems->isNotEmpty())
            <x-table :headers="['No. Pesanan', 'Pelanggan', 'Tanggal', 'Kuantitas', 'Tarif Snapshot', 'Subtotal']">
                @foreach ($recentOrderItems as $item)
                    <tr>
                        <td style="white-space: nowrap; font-weight: var(--font-bold); color: var(--primary);">
                            {{ $item->order?->order_number ?? ('Pesanan #' . $item->order_id) }}
                        </td>
                        <td>
                            <strong>{{ $item->order?->customer?->name ?? 'Pelanggan Umum' }}</strong>
                        </td>
                        <td style="white-space: nowrap; font-size: var(--text-xs);">
                            {{ $item->created_at ? $item->created_at->format('d-m-Y H:i') : '-' }}
                        </td>
                        <td style="white-space: nowrap; font-weight: var(--font-semibold);">
                            {{ $item->quantity }} {{ $item->billing_unit_snapshot instanceof \App\Enums\BillingUnit ? $item->billing_unit_snapshot->label() : ($item->billing_unit_snapshot ?? '') }}
                        </td>
                        <td style="white-space: nowrap;">
                            Rp{{ number_format((float) $item->unit_price, 0, ',', '.') }}
                        </td>
                        <td style="white-space: nowrap; font-weight: var(--font-bold); color: var(--primary-dark);">
                            Rp{{ number_format((float) $item->subtotal, 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </x-table>

            <x-pagination :paginator="$recentOrderItems" />
        @else
            <div class="glass-card" style="padding: var(--space-8);">
                <x-empty-state
                    icon="services"
                    title="Belum Ada Transaksi Tercatat"
                    message="Layanan ini belum pernah dimasukkan ke dalam pesanan cucian pelanggan."
                />
            </div>
        @endif
    </div>
</div>
@endsection
