<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Akses Ditolak | LaundryDesk</title>
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
            max-width: 480px;
            width: 100%;
            text-align: center;
            padding: 3rem 2rem;
        }

        .error-icon {
            width: 4.5rem;
            height: 4.5rem;
            margin: 0 auto 1.5rem auto;
            border-radius: var(--radius-full);
            background-color: var(--status-danger-bg);
            border: 2px solid var(--status-danger-border);
            color: var(--status-danger);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-code {
            font-size: 0.875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--status-danger);
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
                <!-- SVG Gembok / Shield Terkunci -->
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <div class="error-code">Kesalahan 403 (Forbidden)</div>
            <h1 class="error-title">Akses Halaman Ditolak</h1>
            <p class="error-message">
                Akun Anda (<strong>{{ auth()->user()?->role?->label() ?? 'Tamu' }}</strong>) tidak memiliki izin kewenangan untuk mengakses atau memodifikasi modul ini. Silakan kembali ke menu operasional yang sah.
            </p>
            <div>
                <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <span>Kembali ke Dashboard</span>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
