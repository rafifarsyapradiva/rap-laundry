@extends('layouts.app')

@section('title', 'Jejak Audit Aktivitas')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6);">
    <!-- Header Halaman -->
    <div class="glass-card" style="padding: var(--space-6);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-4);">
            <div>
                <h2 style="font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-main); margin: 0;">
                    Jejak Audit Aktivitas Sistem
                </h2>
                <p style="color: var(--text-muted); font-size: var(--text-sm); margin: var(--space-1) 0 0 0;">
                    Catatan kronologis aktivitas login, pesanan, status cucian, pembayaran, void, dan modifikasi data oleh seluruh staf.
                </p>
            </div>
            <span class="badge badge-primary">Khusus Administrator</span>
        </div>
    </div>

    <!-- Formulir Filter & Pencarian Log -->
    <div class="glass-card" style="padding: var(--space-5);">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" style="display: flex; flex-direction: column; gap: var(--space-4);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-4);">
                <!-- Filter Tanggal Mulai -->
                <div class="form-group" style="margin: 0;">
                    <label for="start_date" class="form-label" style="font-size: var(--text-xs);">Dari Tanggal</label>
                    <input
                        type="date"
                        name="start_date"
                        id="start_date"
                        class="form-control"
                        value="{{ request('start_date') }}"
                    >
                </div>

                <!-- Filter Tanggal Sampai -->
                <div class="form-group" style="margin: 0;">
                    <label for="end_date" class="form-label" style="font-size: var(--text-xs);">Sampai Tanggal</label>
                    <input
                        type="date"
                        name="end_date"
                        id="end_date"
                        class="form-control"
                        value="{{ request('end_date') }}"
                    >
                </div>

                <!-- Filter Jenis Tindakan (Action) -->
                <div class="form-group" style="margin: 0;">
                    <label for="action" class="form-label" style="font-size: var(--text-xs);">Jenis Tindakan</label>
                    <select name="action" id="action" class="form-control">
                        <option value="">-- Semua Tindakan --</option>
                        @foreach ($actions as $act)
                            <option value="{{ $act->value }}" {{ request('action') === $act->value ? 'selected' : '' }}>
                                {{ $act->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Pengguna (Aktor) -->
                <div class="form-group" style="margin: 0;">
                    <label for="user_id" class="form-label" style="font-size: var(--text-xs);">Pengguna (Aktor)</label>
                    <select name="user_id" id="user_id" class="form-control">
                        <option value="">-- Semua Pengguna --</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->username }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Pencarian Kata Kunci -->
                <div class="form-group" style="margin: 0;">
                    <label for="search" class="form-label" style="font-size: var(--text-xs);">Pencarian Deskripsi</label>
                    <input
                        type="text"
                        name="search"
                        id="search"
                        class="form-control"
                        placeholder="Kata kunci..."
                        value="{{ request('search') }}"
                    >
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: var(--space-2); align-items: center; border-top: 1px solid var(--border-light); padding-top: var(--space-3);">
                @if(request()->hasAny(['start_date', 'end_date', 'action', 'user_id', 'search']))
                    <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-secondary btn-sm">
                        Reset Filter
                    </a>
                @endif
                <button type="submit" class="btn btn-primary btn-sm">
                    <x-icon name="search" size="14" />
                    <span>Terapkan Filter</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Tabel Catatan Jejak Audit -->
    <x-table :headers="['Waktu (WIB)', 'Aktor', 'Tindakan', 'Deskripsi Aktivitas', 'Detail']">
        @forelse ($logs as $log)
            <tr>
                <!-- Waktu -->
                <td style="white-space: nowrap; font-size: var(--text-xs); color: var(--text-main);">
                    <strong>{{ $log->created_at->format('d-m-Y') }}</strong>
                    <div style="color: var(--text-muted); font-size: 11px;">
                        {{ $log->created_at->format('H:i:s') }} WIB
                    </div>
                </td>

                <!-- Aktor / Pengguna -->
                <td>
                    <strong style="color: var(--text-main);">{{ $log->user?->name ?? 'Sistem' }}</strong>
                    <div style="font-size: var(--text-xs); color: var(--text-muted);">
                        {{ $log->user ? '@' . $log->user->username : 'otomatis' }}
                    </div>
                </td>

                <!-- Tindakan (Badge) -->
                <td>
                    @php
                        $badgeClass = match($log->action) {
                            \App\Enums\ActivityAction::PaymentVoided, \App\Enums\ActivityAction::OrderCancelled => 'badge-danger',
                            \App\Enums\ActivityAction::PaymentRecorded => 'badge-success',
                            \App\Enums\ActivityAction::OrderCreated, \App\Enums\ActivityAction::ServicePriceChanged => 'badge-warning',
                            \App\Enums\ActivityAction::SettingUpdated, \App\Enums\ActivityAction::UserCreated => 'badge-primary',
                            default => 'badge-info',
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }}">
                        {{ $log->action->label() }}
                    </span>
                </td>

                <!-- Deskripsi Aktivitas -->
                <td style="font-size: var(--text-sm); line-height: 1.4;">
                    {{ $log->description }}
                </td>

                <!-- Metadata / Rincian -->
                <td style="font-size: var(--text-xs); min-width: 140px;">
                    @if (!empty($log->metadata) && is_array($log->metadata))
                        <details style="cursor: pointer;">
                            <summary style="color: var(--color-primary); font-weight: 600; outline: none;">
                                Lihat Metadata
                            </summary>
                            <div style="margin-top: 6px; padding: 6px 8px; background: rgba(15, 23, 42, 0.04); border-radius: 4px; font-family: monospace; font-size: 11px; max-width: 280px; overflow-x: auto; white-space: pre-wrap;">
@foreach ($log->metadata as $metaKey => $metaVal)
<strong>{{ $metaKey }}:</strong> {{ is_array($metaVal) ? json_encode($metaVal, JSON_UNESCAPED_UNICODE) : $metaVal }}
@endforeach
                            </div>
                        </details>
                    @else
                        <span style="color: var(--text-muted);">-</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: var(--space-8);">
                    <x-empty-state
                        title="Tidak Ada Catatan Log"
                        description="Tidak ditemukan jejak audit yang sesuai dengan filter pencarian saat ini."
                        icon="clock"
                    />
                </td>
            </tr>
        @endforelse
    </x-table>

    <!-- Pagination -->
    <x-pagination :paginator="$logs" />
</div>
@endsection
