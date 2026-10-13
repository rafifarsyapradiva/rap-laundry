@extends('layouts.app')

@section('title', 'Master Layanan & Tarif')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6);">
    <!-- Header Halaman & Aksi Tambah -->
    <div class="glass-card" style="padding: var(--space-6);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-4);">
            <div>
                <h2 style="font-size: var(--text-2xl); font-weight: var(--font-bold); color: var(--text-main); display: flex; align-items: center; gap: var(--space-2);">
                    <x-icon name="services" size="24" class="icon-primary" />
                    <span>Master Layanan & Tarif</span>
                </h2>
                <p style="color: var(--text-muted); font-size: var(--text-sm); margin-top: var(--space-1);">
                    Kelola jenis layanan cuci, satuan penagihan (Kg, Item, Paket), estimasi durasi, dan tarif resmi LaundryDesk.
                </p>
            </div>
            @if (auth()->check() && auth()->user()->isAdmin())
                <div>
                    <a href="{{ route('services.create') }}" class="btn btn-primary">
                        <x-icon name="plus" size="16" />
                        <span>Tambah Layanan Baru</span>
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Filter & Pencarian Cepat -->
    <div class="glass-panel" style="padding: var(--space-5); display: flex; flex-direction: column; gap: var(--space-4);">
        <!-- Baris Filter Satuan Penagihan -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-3); border-bottom: 1px solid var(--border-subtle); padding-bottom: var(--space-3);">
            <div style="display: flex; gap: var(--space-2); flex-wrap: wrap; align-items: center;">
                <span style="font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; margin-right: var(--space-1);">
                    Satuan:
                </span>
                <a href="{{ route('services.index', array_merge(request()->query(), ['unit' => 'all', 'page' => 1])) }}"
                   class="btn btn-sm {{ $unit === 'all' ? 'btn-primary' : 'btn-outline' }}">
                    <span>Semua Satuan</span>
                    <span class="badge {{ $unit === 'all' ? 'badge-neutral' : 'badge-primary' }}">{{ $unitCounts['all'] }}</span>
                </a>
                <a href="{{ route('services.index', array_merge(request()->query(), ['unit' => 'kg', 'page' => 1])) }}"
                   class="btn btn-sm {{ $unit === 'kg' ? 'btn-primary' : 'btn-outline' }}">
                    <span>Kiloan (Kg)</span>
                    <span class="badge {{ $unit === 'kg' ? 'badge-neutral' : 'badge-info' }}">{{ $unitCounts['kg'] }}</span>
                </a>
                <a href="{{ route('services.index', array_merge(request()->query(), ['unit' => 'item', 'page' => 1])) }}"
                   class="btn btn-sm {{ $unit === 'item' ? 'btn-primary' : 'btn-outline' }}">
                    <span>Per Satuan (Item)</span>
                    <span class="badge {{ $unit === 'item' ? 'badge-neutral' : 'badge-info' }}">{{ $unitCounts['item'] }}</span>
                </a>
                <a href="{{ route('services.index', array_merge(request()->query(), ['unit' => 'package', 'page' => 1])) }}"
                   class="btn btn-sm {{ $unit === 'package' ? 'btn-primary' : 'btn-outline' }}">
                    <span>Paket Khusus</span>
                    <span class="badge {{ $unit === 'package' ? 'badge-neutral' : 'badge-info' }}">{{ $unitCounts['package'] }}</span>
                </a>
            </div>

            <!-- Filter Status Keaktifan -->
            <div style="display: flex; gap: var(--space-2); flex-wrap: wrap; align-items: center;">
                <span style="font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; margin-right: var(--space-1);">
                    Status:
                </span>
                <a href="{{ route('services.index', array_merge(request()->query(), ['status' => 'active', 'page' => 1])) }}"
                   class="btn btn-sm {{ $status === 'active' ? 'btn-primary' : 'btn-outline' }}">
                    <span>Aktif</span>
                    <span class="badge {{ $status === 'active' ? 'badge-neutral' : 'badge-success' }}">{{ $counts['active'] }}</span>
                </a>
                <a href="{{ route('services.index', array_merge(request()->query(), ['status' => 'inactive', 'page' => 1])) }}"
                   class="btn btn-sm {{ $status === 'inactive' ? 'btn-primary' : 'btn-outline' }}">
                    <span>Nonaktif</span>
                    <span class="badge {{ $status === 'inactive' ? 'badge-neutral' : 'badge-warning' }}">{{ $counts['inactive'] }}</span>
                </a>
                <a href="{{ route('services.index', array_merge(request()->query(), ['status' => 'archived', 'page' => 1])) }}"
                   class="btn btn-sm {{ $status === 'archived' ? 'btn-primary' : 'btn-outline' }}">
                    <span>Terarsip</span>
                    <span class="badge {{ $status === 'archived' ? 'badge-neutral' : 'badge-neutral' }}">{{ $counts['archived'] }}</span>
                </a>
            </div>
        </div>

        <!-- Form Pencarian Cepat -->
        <form action="{{ route('services.index') }}" method="GET" style="display: flex; gap: var(--space-3); flex-wrap: wrap; align-items: center;">
            <input type="hidden" name="unit" value="{{ $unit }}">
            <input type="hidden" name="status" value="{{ $status }}">

            <div style="flex: 1; min-width: 260px; position: relative;">
                <input
                    type="text"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Cari nama layanan atau deskripsi pengerjaan..."
                    class="form-input"
                    style="padding-left: 2.5rem;"
                >
                <div style="position: absolute; left: 0.875rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;">
                    <x-icon name="search" size="16" />
                </div>
            </div>

            <div style="display: flex; gap: var(--space-2);">
                <button type="submit" class="btn btn-primary">
                    <x-icon name="filter" size="16" />
                    <span>Filter Layanan</span>
                </button>
                @if ($search !== '' || $unit !== 'all' || $status !== 'active')
                    <a href="{{ route('services.index') }}" class="btn btn-outline" title="Reset Filter">
                        <x-icon name="x" size="16" />
                        <span>Reset</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabel Master Layanan -->
    @if ($services->isNotEmpty())
        <x-table :headers="['Nama Layanan', 'Satuan', 'Tarif / Harga', 'Estimasi Pengerjaan', 'Penggunaan', 'Status', 'Aksi']">
            @foreach ($services as $service)
                <tr style="{{ $service->trashed() ? 'opacity: 0.85;' : '' }}">
                    <td>
                        <a href="{{ route('services.show', $service->id) }}" style="font-weight: var(--font-bold); color: var(--text-main); text-decoration: none; display: block;">
                            {{ $service->name }}
                        </a>
                        @if ($service->description)
                            <span style="font-size: var(--text-xs); color: var(--text-muted); display: block; max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $service->description }}
                            </span>
                        @endif
                    </td>
                    <td style="white-space: nowrap;">
                        <span class="badge {{ $service->billing_unit?->value === 'kg' ? 'badge-primary' : ($service->billing_unit?->value === 'item' ? 'badge-info' : 'badge-warning') }}">
                            {{ $service->billing_unit?->label() ?? 'Satuan' }}
                        </span>
                    </td>
                    <td style="white-space: nowrap; font-weight: var(--font-bold); color: var(--primary-dark);">
                        {{ $service->formatted_price }}
                    </td>
                    <td style="white-space: nowrap; font-size: var(--text-xs); color: var(--text-main);">
                        <span style="display: inline-flex; align-items: center; gap: var(--space-1);">
                            <x-icon name="clock" size="14" class="icon-muted" />
                            <span>{{ $service->estimated_duration_text }}</span>
                        </span>
                    </td>
                    <td style="white-space: nowrap;">
                        <span class="badge badge-neutral">
                            {{ $service->order_items_count }} kali
                        </span>
                    </td>
                    <td style="white-space: nowrap;">
                        @if ($service->trashed())
                            <span class="badge badge-neutral">Terarsip</span>
                        @elseif ($service->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-warning">Nonaktif</span>
                        @endif
                    </td>
                    <td style="white-space: nowrap;">
                        <div style="display: flex; gap: var(--space-2); align-items: center;">
                            <!-- Detail -->
                            <a href="{{ route('services.show', $service->id) }}" class="btn btn-secondary btn-sm" title="Lihat Detail Spesifikasi">
                                <x-icon name="eye" size="14" />
                                <span>Detail</span>
                            </a>

                            @if (auth()->check() && auth()->user()->isAdmin())
                                @if (! $service->trashed())
                                    <!-- Ubah -->
                                    <a href="{{ route('services.edit', $service->id) }}" class="btn btn-outline btn-sm" title="Ubah Layanan & Tarif">
                                        <x-icon name="edit" size="14" />
                                    </a>

                                    <!-- Arsipkan -->
                                    <button
                                        type="button"
                                        class="btn btn-outline btn-sm"
                                        style="color: var(--status-danger); border-color: var(--status-danger-border);"
                                        title="Arsipkan Layanan"
                                        onclick="window.LaundryDesk.openConfirmModal({
                                            action: '{{ route('services.destroy', $service->id) }}',
                                            method: 'DELETE',
                                            title: 'Arsipkan Layanan',
                                            message: 'Apakah Anda yakin ingin mengarsipkan layanan \'{{ $service->name }}\'? Layanan tidak akan dapat dipilih saat pembuatan pesanan baru.',
                                            confirmText: 'Ya, Arsipkan',
                                            confirmClass: 'btn-danger'
                                        })"
                                    >
                                        <x-icon name="trash" size="14" />
                                    </button>
                                @else
                                    <!-- Pulihkan -->
                                    <form action="{{ route('services.restore', $service->id) }}" method="POST" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm" title="Pulihkan Layanan dari Arsip">
                                            <x-icon name="check" size="14" />
                                            <span>Pulihkan</span>
                                        </button>
                                    </form>

                                    <!-- Hapus Permanen (Jika tanpa riwayat transaksi) -->
                                    @if ($service->order_items_count == 0)
                                        <button
                                            type="button"
                                            class="btn btn-danger btn-sm"
                                            title="Hapus Permanen (Tanpa Riwayat)"
                                            onclick="window.LaundryDesk.openConfirmModal({
                                                action: '{{ route('services.force-delete', $service->id) }}',
                                                method: 'DELETE',
                                                title: 'Hapus Permanen Layanan',
                                                message: 'Perhatian: Layanan \'{{ $service->name }}\' belum pernah digunakan dalam transaksi cucian dan akan dihapus permanen dari basis data.',
                                                confirmText: 'Hapus Permanen',
                                                confirmClass: 'btn-danger'
                                            })"
                                        >
                                            <x-icon name="trash" size="14" />
                                        </button>
                                    @endif
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-table>

        <x-pagination :paginator="$services" />
    @else
        <div class="glass-card" style="padding: var(--space-8);">
            <x-empty-state
                icon="services"
                title="Tidak Ada Layanan Ditemukan"
                message="{{ $search !== '' ? 'Tidak ada layanan yang cocok dengan kata kunci pencarian \"' . $search . '\".' : 'Belum ada data layanan pada kategori atau filter ini.' }}"
            />
            @if ($search !== '' || $unit !== 'all' || $status !== 'active')
                <div style="text-align: center; margin-top: var(--space-4);">
                    <a href="{{ route('services.index') }}" class="btn btn-secondary">
                        <x-icon name="x" size="16" />
                        <span>Kembali ke Semua Layanan Aktif</span>
                    </a>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
