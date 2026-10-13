@extends('layouts.app')

@section('title', 'Data Pelanggan')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6);">
    <!-- Header Halaman & Aksi Tambah -->
    <div class="glass-card" style="padding: var(--space-6);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-4);">
            <div>
                <h2 style="font-size: var(--text-2xl); font-weight: var(--font-bold); color: var(--text-main); display: flex; align-items: center; gap: var(--space-2);">
                    <x-icon name="customers" size="24" class="icon-primary" />
                    <span>Data Pelanggan</span>
                </h2>
                <p style="color: var(--text-muted); font-size: var(--text-sm); margin-top: var(--space-1);">
                    Kelola kontak, status keaktifan, dan riwayat transaksi cucian pelanggan.
                </p>
            </div>
            <div>
                <a href="{{ route('customers.create') }}" class="btn btn-primary">
                    <x-icon name="plus" size="16" />
                    <span>Tambah Pelanggan Baru</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Filter & Pencarian Cepat -->
    <div class="glass-panel" style="padding: var(--space-5); display: flex; flex-direction: column; gap: var(--space-4);">
        <!-- Status Tab Filter -->
        <div style="display: flex; gap: var(--space-2); flex-wrap: wrap; border-bottom: 1px solid var(--border-subtle); padding-bottom: var(--space-3);">
            <a href="{{ route('customers.index', array_merge(request()->query(), ['status' => 'active', 'page' => 1])) }}"
               class="btn btn-sm {{ $status === 'active' ? 'btn-primary' : 'btn-outline' }}">
                <span>Aktif</span>
                <span class="badge {{ $status === 'active' ? 'badge-neutral' : 'badge-primary' }}">{{ $counts['active'] }}</span>
            </a>
            <a href="{{ route('customers.index', array_merge(request()->query(), ['status' => 'inactive', 'page' => 1])) }}"
               class="btn btn-sm {{ $status === 'inactive' ? 'btn-primary' : 'btn-outline' }}">
                <span>Nonaktif</span>
                <span class="badge {{ $status === 'inactive' ? 'badge-neutral' : 'badge-warning' }}">{{ $counts['inactive'] }}</span>
            </a>
            <a href="{{ route('customers.index', array_merge(request()->query(), ['status' => 'archived', 'page' => 1])) }}"
               class="btn btn-sm {{ $status === 'archived' ? 'btn-primary' : 'btn-outline' }}">
                <span>Terarsip</span>
                <span class="badge {{ $status === 'archived' ? 'badge-neutral' : 'badge-neutral' }}">{{ $counts['archived'] }}</span>
            </a>
            <a href="{{ route('customers.index', array_merge(request()->query(), ['status' => 'all', 'page' => 1])) }}"
               class="btn btn-sm {{ $status === 'all' ? 'btn-primary' : 'btn-outline' }}">
                <span>Semua Data</span>
                <span class="badge {{ $status === 'all' ? 'badge-neutral' : 'badge-info' }}">{{ $counts['all'] }}</span>
            </a>
        </div>

        <!-- Form Filter & Pencarian -->
        <form action="{{ route('customers.index') }}" method="GET" style="display: flex; gap: var(--space-3); flex-wrap: wrap; align-items: center;">
            <input type="hidden" name="status" value="{{ $status }}">

            <div style="flex: 1; min-width: 260px; position: relative;">
                <input
                    type="text"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Cari berdasarkan nama, nomor telepon, atau kode (CUST-...)..."
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
                    <span>Filter Data</span>
                </button>
                @if ($search !== '' || $status !== 'active')
                    <a href="{{ route('customers.index') }}" class="btn btn-outline" title="Reset Filter">
                        <x-icon name="x" size="16" />
                        <span>Reset</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabel Data Pelanggan -->
    @if ($customers->isNotEmpty())
        <x-table :headers="['Kode', 'Nama Pelanggan', 'Nomor Telepon', 'Alamat', 'Pesanan', 'Status', 'Aksi']">
            @foreach ($customers as $customer)
                <tr style="{{ $customer->trashed() ? 'opacity: 0.85;' : '' }}">
                    <td style="white-space: nowrap;">
                        <a href="{{ route('customers.show', $customer->id) }}" style="font-weight: var(--font-bold); color: var(--primary); text-decoration: none;">
                            {{ $customer->customer_code }}
                        </a>
                    </td>
                    <td>
                        <strong style="color: var(--text-main); display: block;">{{ $customer->name }}</strong>
                        @if ($customer->notes)
                            <span style="font-size: var(--text-xs); color: var(--text-muted); display: block; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $customer->notes }}
                            </span>
                        @endif
                    </td>
                    <td style="white-space: nowrap;">
                        <a href="tel:{{ $customer->phone }}" style="color: var(--text-main); text-decoration: none; display: inline-flex; align-items: center; gap: var(--space-1);">
                            <span>{{ $customer->formatted_phone }}</span>
                        </a>
                    </td>
                    <td style="max-width: 220px; font-size: var(--text-xs); color: var(--text-muted);">
                        {{ $customer->address ? Str::limit($customer->address, 50) : '-' }}
                    </td>
                    <td style="white-space: nowrap;">
                        <span class="badge badge-info">
                            {{ $customer->orders_count }} pesanan
                        </span>
                    </td>
                    <td style="white-space: nowrap;">
                        @if ($customer->trashed())
                            <span class="badge badge-neutral">Terarsip</span>
                        @elseif ($customer->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-warning">Nonaktif</span>
                        @endif
                    </td>
                    <td style="white-space: nowrap;">
                        <div style="display: flex; gap: var(--space-2); align-items: center;">
                            <!-- Tombol Detail -->
                            <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-secondary btn-sm" title="Lihat Profil & Riwayat">
                                <x-icon name="eye" size="14" />
                                <span>Detail</span>
                            </a>

                            @if (! $customer->trashed())
                                <!-- Tombol Ubah -->
                                <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-outline btn-sm" title="Ubah Data">
                                    <x-icon name="edit" size="14" />
                                </a>

                                <!-- Tombol Arsipkan -->
                                <button
                                    type="button"
                                    class="btn btn-outline btn-sm"
                                    style="color: var(--status-danger); border-color: var(--status-danger-border);"
                                    title="Arsipkan Pelanggan"
                                    onclick="window.LaundryDesk.openConfirmModal({
                                        action: '{{ route('customers.destroy', $customer->id) }}',
                                        method: 'DELETE',
                                        title: 'Arsipkan Pelanggan',
                                        message: 'Apakah Anda yakin ingin mengarsipkan pelanggan {{ $customer->name }} ({{ $customer->customer_code }})? Pelanggan tidak akan muncul dalam opsi kasir saat membuat pesanan baru.',
                                        confirmText: 'Ya, Arsipkan',
                                        confirmClass: 'btn-danger'
                                    })"
                                >
                                    <x-icon name="trash" size="14" />
                                </button>
                            @else
                                <!-- Tombol Pulihkan -->
                                <form action="{{ route('customers.restore', $customer->id) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm" title="Pulihkan Pelanggan dari Arsip">
                                        <x-icon name="check" size="14" />
                                        <span>Pulihkan</span>
                                    </button>
                                </form>

                                <!-- Tombol Hapus Permanen (Hanya Admin dan jika tanpa pesanan) -->
                                @if (auth()->user()?->isAdmin() && $customer->orders_count == 0)
                                    <button
                                        type="button"
                                        class="btn btn-danger btn-sm"
                                        title="Hapus Permanen (Tanpa Riwayat)"
                                        onclick="window.LaundryDesk.openConfirmModal({
                                            action: '{{ route('customers.force-delete', $customer->id) }}',
                                            method: 'DELETE',
                                            title: 'Hapus Permanen Pelanggan',
                                            message: 'Perhatian: Pelanggan {{ $customer->name }} belum memiliki riwayat transaksi dan akan dihapus secara permanen dari basis data. Tindakan ini tidak dapat dibatalkan.',
                                            confirmText: 'Hapus Permanen',
                                            confirmClass: 'btn-danger'
                                        })"
                                    >
                                        <x-icon name="trash" size="14" />
                                    </button>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-table>

        <!-- Pagination -->
        <x-pagination :paginator="$customers" />
    @else
        <div class="glass-card" style="padding: var(--space-8);">
            <x-empty-state
                icon="customers"
                title="Tidak Ada Data Pelanggan Ditemukan"
                message="{{ $search !== '' ? 'Tidak ada pelanggan yang cocok dengan kata kunci pencarian \"' . $search . '\". Silakan coba kata kunci lain atau reset filter.' : 'Belum ada data pelanggan yang tersimpan dalam kategori ini. Silakan tambahkan data pelanggan baru.' }}"
            />
            @if ($search !== '' || $status !== 'active')
                <div style="text-align: center; margin-top: var(--space-4);">
                    <a href="{{ route('customers.index') }}" class="btn btn-secondary">
                        <x-icon name="x" size="16" />
                        <span>Kembali ke Semua Pelanggan Aktif</span>
                    </a>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
