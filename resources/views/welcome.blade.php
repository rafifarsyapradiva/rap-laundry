<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'LaundryDesk') }} — Pengelolaan Cuci Pakaian Offline</title>

    <!-- Styles: 100% Offline, Zero External CDN -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar">
        <a href="{{ url('/') }}" class="nav-brand">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="4" y="2" width="16" height="20" rx="2"/>
                <circle cx="12" cy="13" r="5"/>
                <path d="M12 10a3 3 0 0 1 3 3"/>
                <circle cx="8" cy="5" r="1" fill="currentColor"/>
                <circle cx="12" cy="5" r="1" fill="currentColor"/>
            </svg>
            Laundry<span>Desk</span>
        </a>

        <div style="display: flex; align-items: center; gap: 1rem;">
            <div class="offline-indicator">
                <span class="offline-dot"></span>
                <span>Mode Offline Aktif</span>
            </div>

            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-sm">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-secondary btn-sm">Masuk Kasir</a>
                @endauth
            @endif
        </div>
    </nav>

    <!-- Main Container -->
    <main class="container" style="padding-top: 2.5rem; padding-bottom: 3rem;">
        <!-- Hero Header -->
        <div class="glass-card" style="padding: 2.5rem; text-align: center; margin-bottom: 2rem;">
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                <span class="badge badge-primary">Laravel 12 & MySQL Lokal</span>
                <span class="badge badge-success">Zero CDN Dependency</span>
            </div>

            <h1 style="font-size: 2.25rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.75rem; letter-spacing: -0.025em;">
                Sistem Operasional Laundry Terpusat
            </h1>
            <p style="font-size: 1.125rem; color: var(--text-muted); max-width: 640px; margin: 0 auto 1.5rem auto;">
                Aplikasi pencatatan transaksi cuci pakaian, pelacakan progres cucian, dan pembukuan kas mandiri yang dirancang beroperasi 100% tanpa internet.
            </p>

            <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                <a href="{{ url('/') }}" class="btn btn-primary btn-lg">
                    <span>Mulai Transaksi Baru</span>
                </a>
                <a href="#fitur" class="btn btn-secondary btn-lg">
                    <span>Pelajari Fitur</span>
                </a>
            </div>
        </div>

        <!-- Metrics Overview Grid -->
        <div class="grid grid-cols-4" style="margin-bottom: 2rem;">
            <div class="glass-card stat-card">
                <div class="stat-header">
                    <span class="stat-label">Mode Operasi</span>
                    <div class="stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="2" y1="12" x2="22" y2="12"/>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                        </svg>
                    </div>
                </div>
                <div class="stat-value" style="font-size: 1.5rem;">Offline-First</div>
                <span class="stat-hint">100% Berjalan di Jaringan Lokal</span>
            </div>

            <div class="glass-card stat-card">
                <div class="stat-header">
                    <span class="stat-label">Penyimpanan Basis Data</span>
                    <div class="stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <ellipse cx="12" cy="5" rx="9" ry="3"/>
                            <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
                            <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                        </svg>
                    </div>
                </div>
                <div class="stat-value" style="font-size: 1.5rem;">MariaDB / MySQL</div>
                <span class="stat-hint">Koneksi XAMPP Port 3306 Terhubung</span>
            </div>

            <div class="glass-card stat-card">
                <div class="stat-header">
                    <span class="stat-label">Integritas Keuangan</span>
                    <div class="stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="4" width="20" height="16" rx="2"/>
                            <line x1="2" y1="10" x2="22" y2="10"/>
                        </svg>
                    </div>
                </div>
                <div class="stat-value" style="font-size: 1.5rem;">DECIMAL Presisi</div>
                <span class="stat-hint">Perhitungan Matematis di Sisi Server</span>
            </div>

            <div class="glass-card stat-card">
                <div class="stat-header">
                    <span class="stat-label">Format Cetak Nota</span>
                    <div class="stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 6 2 18 2 18 9"/>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                            <rect x="6" y="14" width="12" height="8"/>
                        </svg>
                    </div>
                </div>
                <div class="stat-value" style="font-size: 1.5rem;">Thermal & A4</div>
                <span class="stat-hint">Optimasi CSS @media print</span>
            </div>
        </div>

        <!-- System Architecture Highlights -->
        <div id="fitur" class="grid grid-cols-3">
            <div class="glass-card" style="padding: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <div class="stat-icon" style="background-color: var(--status-success-bg); color: var(--status-success);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                    <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--text-main);">Snapshot Harga Transaksi</h3>
                </div>
                <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.6;">
                    Harga master yang diubah di masa depan tidak akan pernah merusak nilai nota pesanan lama. Seluruh tarif tersimpan permanen pada item pesanan.
                </p>
            </div>

            <div class="glass-card" style="padding: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <div class="stat-icon" style="background-color: var(--primary-light); color: var(--primary);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--text-main);">Pelacakan 8 Tahap Progres</h3>
                </div>
                <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.6;">
                    Alur operasional transparan dari penerimaan pakaian, cuci, kering, setrika, siap diambil, hingga penyerahan dengan pencatatan waktu dan operator.
                </p>
            </div>

            <div class="glass-card" style="padding: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <div class="stat-icon" style="background-color: var(--status-info-bg); color: var(--status-info);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                    <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--text-main);">Pencatatan Kas & Void Audit</h3>
                </div>
                <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.6;">
                    Pencatatan kas masuk terpisah dari nilai pesanan. Koreksi transaksi dilakukan melalui mekanisme Void yang terekam aman dalam audit log.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <footer style="margin-top: 3rem; text-align: center; font-size: 0.8125rem; color: var(--text-muted);">
            <p>&copy; {{ date('Y') }} LaundryDesk (RAP Laundry). Dibangun dengan standar rekayasa perangkat lunak Laravel 12 & MariaDB.</p>
            <p style="margin-top: 0.25rem;">Zona Waktu: Asia/Jakarta (WIB) &bull; Mandiri Luring &bull; 100% Offline Ready</p>
        </footer>
    </main>

    <!-- Core Offline Scripts -->
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
