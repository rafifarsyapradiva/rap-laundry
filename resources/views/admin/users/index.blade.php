<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna & Staf — LaundryDesk</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="navbar">
        <div class="nav-brand">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="2" width="20" height="20" rx="3" ry="3"></rect>
                <circle cx="12" cy="13" r="5"></circle>
            </svg>
            <span>LaundryDesk — Manajemen Pengguna</span>
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
                        Daftar Pengguna dan Staf Operasional
                    </h1>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem;">
                        Area ini hanya dapat diakses oleh Pemilik/Admin sistem.
                    </p>
                </div>
                <span class="badge badge-primary">Khusus Administrator</span>
            </div>
        </div>
    </main>
</body>
</html>
