@extends('layouts.app')

@section('title', 'Jejak Audit Aktivitas')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6);">
    <div class="glass-card" style="padding: var(--space-6);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-4);">
            <div>
                <h2 style="font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-main);">
                    Jejak Audit Aktivitas Sistem
                </h2>
                <p style="color: var(--text-muted); font-size: var(--text-sm); margin-top: var(--space-1);">
                    Catatan kronologis aktivitas login, transaksi, dan perubahan konfigurasi oleh seluruh staf (Khusus Administrator).
                </p>
            </div>
            <span class="badge badge-primary">Khusus Administrator</span>
        </div>
    </div>

    <x-table :headers="['Waktu (WIB)', 'Aktor / Pengguna', 'Tindakan', 'Deskripsi Aktivitas', 'IP Address']">
        @forelse ($logs as $log)
            <tr>
                <td style="white-space: nowrap; font-size: var(--text-xs);">
                    {{ $log->created_at->format('d-m-Y H:i:s') }}
                </td>
                <td>
                    <strong>{{ $log->user?->name ?? 'Sistem' }}</strong>
                    <div style="font-size: var(--text-xs); color: var(--text-muted);">
                        {{ $log->user?->username ?? '-' }}
                    </div>
                </td>
                <td>
                    <span class="badge badge-info">
                        {{ $log->action->label() }}
                    </span>
                </td>
                <td>
                    {{ $log->description }}
                </td>
                <td style="font-size: var(--text-xs); color: var(--text-muted);">
                    {{ $log->metadata['ip_address'] ?? '-' }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: var(--space-8);">
                    Belum ada catatan aktivitas yang terekam.
                </td>
            </tr>
        @endforelse
    </x-table>

    <x-pagination :paginator="$logs" />
</div>
@endsection
