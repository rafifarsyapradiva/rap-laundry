<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard — LaundryDesk (RAP Laundry)</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="navbar">
        <div class="nav-brand">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="2" width="20" height="20" rx="3" ry="3"></rect>
                <circle cx="12" cy="13" r="5"></circle>
            </svg>
            <span>LaundryDesk</span>
        </div>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="font-weight: 600; font-size: 0.875rem;">{{ auth()->user()->name }}</span>
                <span class="badge {{ auth()->user()->isAdmin() ? 'badge-primary' : 'badge-info' }}">
                    {{ auth()->user()->role->label() }}
                </span>
            </div>
            <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm" id="btn-logout">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </header>

    <main class="container" style="padding-top: 2rem; padding-bottom: 2rem;">
        <div class="glass-card" style="padding: 2rem; margin-bottom: 1.5rem;">
            <h1 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-main);">
                Selamat Datang, {{ auth()->user()->name }}!
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Anda berhasil masuk ke sistem <strong>LaundryDesk (RAP Laundry)</strong> sebagai 
                <strong>{{ auth()->user()->role->label() }}</strong>.
            </p>
            <div style="margin-top: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                <div class="stat-card glass-panel" style="flex: 1; min-width: 220px;">
                    <span class="stat-label">Nama Pengguna</span>
                    <span style="font-size: 1.1rem; font-weight: 600;">{{ auth()->user()->username }}</span>
                    <span class="stat-hint">ID Pengguna: #{{ auth()->user()->id }}</span>
                </div>
                <div class="stat-card glass-panel" style="flex: 1; min-width: 220px;">
                    <span class="stat-label">Alamat Email</span>
                    <span style="font-size: 1.1rem; font-weight: 600;">{{ auth()->user()->email ?? '-' }}</span>
                    <span class="stat-hint">Status: Terverifikasi Lokal</span>
                </div>
                <div class="stat-card glass-panel" style="flex: 1; min-width: 220px;">
                    <span class="stat-label">Hak Akses</span>
                    <span style="font-size: 1.1rem; font-weight: 600;">{{ auth()->user()->role->label() }}</span>
                    <span class="stat-hint">Status Akun: {{ auth()->user()->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </div>
            </div>
        </div>

        <div class="glass-panel" style="padding: 1.5rem;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h2 style="font-size: 1.125rem; font-weight: 600; color: var(--text-main);">
                        Status Autentikasi Sistem
                    </h2>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
                        Task 3.1 (Login/Logout) telah aktif dan memproteksi seluruh route internal sistem laundry.
                    </p>
                </div>
                <div class="offline-indicator">
                    <span class="offline-dot"></span>
                    <span>100% Offline Ready</span>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
