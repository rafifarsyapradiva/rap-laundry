<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — Kesalahan Server | LaundryDesk</title>
    <!-- Aset CSS 100% Offline (Tanpa CDN / Google Fonts) -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        .error-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .error-card {
            max-width: 520px;
            width: 100%;
            text-align: center;
            padding: 3rem 2rem;
        }

        .error-icon {
            width: 4.5rem;
            height: 4.5rem;
            margin: 0 auto 1.5rem auto;
            border-radius: var(--radius-full);
            background-color: var(--status-danger-bg, #fee2e2);
            border: 2px solid var(--status-danger-border, #fca5a5);
            color: var(--status-danger, #dc2626);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-code {
            font-size: 0.875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--status-danger, #dc2626);
            margin-bottom: 0.5rem;
        }

        .error-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.75rem;
        }

        .error-message {
            font-size: 0.95rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="error-wrapper">
        <div class="glass-card error-card">
            <div class="error-icon">
                <!-- SVG Server / Peringatan Sistem -->
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                    <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                    <line x1="6" y1="6" x2="6.01" y2="6"></line>
                    <line x1="6" y1="18" x2="6.01" y2="18"></line>
                </svg>
            </div>
            <div class="error-code">Kesalahan 500 (Internal Server Error)</div>
            <h1 class="error-title">Terjadi Gangguan Sistem</h1>
            <p class="error-message">
                Sistem mengalami kendala saat memproses permintaan Anda. Pastikan database lokal MariaDB/MySQL pada kontrol panel XAMPP dalam keadaan aktif dan terhubung normal.
            </p>
            <div>
                <a href="{{ url('/') }}" class="btn btn-primary btn-lg">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <span>Kembali ke Halaman Utama</span>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
