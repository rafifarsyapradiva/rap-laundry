<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jejak Audit Aktivitas — LaundryDesk</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="navbar">
        <div class="nav-brand">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="2" width="20" height="20" rx="3" ry="3"></rect>
                <circle cx="12" cy="13" r="5"></circle>
            </svg>
            <span>LaundryDesk — Jejak Audit Aktivitas</span>
        </div>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm">Ke Dashboard</a>
            <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                @csrf
                <button type="submit" class="btn btn-danger btn-sm">Keluar</button>
            </form>
        </div>
    </header>

    <main class="container" style="padding-top: 2rem; padding-bottom: 2rem;">
        <div class="glass-card" style="padding: 2rem; margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main);">
                        Jejak Audit Aktivitas Sistem
                    </h1>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem;">
                        Catatan kronologis aktivitas login, transaksi, dan perubahan konfigurasi oleh seluruh staf (Khusus Administrator).
                    </p>
                </div>
                <span class="badge badge-primary">Khusus Administrator</span>
            </div>
        </div>

        <div class="glass-panel" style="padding: 1.5rem;">
            <div class="table-container">
                <table class="table-glass">
                    <thead>
                        <tr>
                            <th>Waktu (WIB)</th>
                            <th>Aktor / Pengguna</th>
                            <th>Tindakan</th>
                            <th>Deskripsi Aktivitas</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td style="white-space: nowrap; font-size: 0.8125rem;">
                                    {{ $log->created_at->format('d-m-Y H:i:s') }}
                                </td>
                                <td>
                                    <strong>{{ $log->user?->name ?? 'Sistem' }}</strong>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
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
                                <td style="font-size: 0.8125rem; color: var(--text-muted);">
                                    {{ $log->metadata['ip_address'] ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                    Belum ada catatan aktivitas yang terekam.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($logs->hasPages())
                <div style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 0.8125rem; color: var(--text-muted);">
                        Menampilkan {{ $logs->firstItem() }} - {{ $logs->lastItem() }} dari {{ $logs->total() }} catatan
                    </div>
                    <div>
                        {{ $logs->links() }}
                    </div>
                </div>
            @endif
        </div>
    </main>
</body>
</html>
