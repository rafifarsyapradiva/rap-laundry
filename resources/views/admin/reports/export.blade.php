<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ekspor Laporan Keuangan — LaundryDesk</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="navbar">
        <div class="nav-brand">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="2" width="20" height="20" rx="3" ry="3"></rect>
                <circle cx="12" cy="13" r="5"></circle>
            </svg>
            <span>LaundryDesk — Ekspor Laporan</span>
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
                        Ekspor Laporan Keuangan & Transaksi
                    </h1>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem;">
                        Unduh rekapitulasi pendapatan, piutang, dan riwayat pesanan (Khusus Pemilik/Administrator).
                    </p>
                </div>
                <span class="badge badge-primary">Khusus Administrator</span>
            </div>
        </div>

        <div class="glass-panel" style="padding: 1.5rem;">
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <div class="stat-card glass-panel" style="flex: 1; min-width: 250px;">
                    <span class="stat-label">Format Data Tersedia</span>
                    <span style="font-size: 1.25rem; font-weight: 700;">CSV / Excel / PDF Cetak</span>
                    <span class="stat-hint">Dapat dibuka offline dengan aplikasi spreadsheet lokal</span>
                </div>
                <div class="stat-card glass-panel" style="flex: 1; min-width: 250px;">
                    <span class="stat-label">Tingkat Hak Akses</span>
                    <span style="font-size: 1.25rem; font-weight: 700; color: var(--primary);">Restricted</span>
                    <span class="stat-hint">Kasir dibatasi dari akses data finansial agregat</span>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
