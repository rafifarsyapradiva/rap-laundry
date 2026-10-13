<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Halaman Tidak Ditemukan | LaundryDesk</title>
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
            background-color: var(--status-warning-bg, #fef3c7);
            border: 2px solid var(--status-warning-border, #fcd34d);
            color: var(--status-warning, #d97706);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-code {
            font-size: 0.875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--status-warning, #d97706);
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
                <!-- SVG Berkas Dicari / Tanda Tanya -->
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    <line x1="11" y1="8" x2="11" y2="8.01"></line>
                    <line x1="11" y1="12" x2="11" y2="14"></line>
                </svg>
            </div>
            <div class="error-code">Kesalahan 404 (Not Found)</div>
            <h1 class="error-title">Halaman Tidak Ditemukan</h1>
            <p class="error-message">
                Halaman, faktur pesanan, atau data yang Anda cari tidak tersedia atau mungkin telah dipindahkan.
            </p>
            <div>
                <a href="{{ url('/') }}" class="btn btn-primary btn-lg">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <span>Kembali ke Beranda</span>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
