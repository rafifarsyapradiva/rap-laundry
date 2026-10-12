@extends('layouts.auth')

@section('title', 'Masuk ke Sistem')

@section('content')
<div class="glass-card auth-card">
    <div class="auth-brand">
        <div class="auth-brand-icon">
            <!-- Offline SVG Icon Laundry Mesin Cuci -->
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="2" width="20" height="20" rx="3" ry="3"></rect>
                <circle cx="12" cy="13" r="5"></circle>
                <path d="M12 18a5 5 0 0 0 4-2"></path>
                <line x1="6" y1="6" x2="6.01" y2="6"></line>
                <line x1="10" y1="6" x2="10.01" y2="6"></line>
            </svg>
        </div>
        <h1 class="auth-brand-title">RAP Laundry</h1>
        <p class="auth-brand-subtitle">LaundryDesk — Sistem Operasional Laundry</p>
    </div>

    <!-- Notifikasi Pesan Flash Status -->
    @if (session('status'))
        <div class="alert alert-success" role="alert">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            <div>{{ session('status') }}</div>
        </div>
    @endif

    <!-- Notifikasi Error Validasi -->
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <div>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" id="login-form" novalidate>
        @csrf

        <!-- Input Identitas (Email atau Username) -->
        <div class="form-group">
            <label for="identity" class="form-label">Email atau Nama Pengguna</label>
            <input 
                type="text" 
                id="identity" 
                name="identity" 
                value="{{ old('identity') }}" 
                class="form-input @error('identity') is-invalid @enderror" 
                placeholder="misal: admin atau kasir@raplaundry.local" 
                required 
                autofocus 
                autocomplete="username"
            >
        </div>

        <!-- Input Kata Sandi -->
        <div class="form-group">
            <label for="password" class="form-label">Kata Sandi</label>
            <div class="password-toggle-wrap">
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-input @error('password') is-invalid @enderror" 
                    placeholder="Masukkan kata sandi Anda" 
                    required 
                    autocomplete="current-password"
                >
                <button type="button" class="password-toggle-btn" id="toggle-password" title="Tampilkan / sembunyikan kata sandi" aria-label="Lihat kata sandi">
                    <!-- SVG Mata -->
                    <svg id="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Pilihan Ingat Saya -->
        <div class="remember-wrap">
            <label class="checkbox-label" for="remember">
                <input type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                <span>Ingat saya di perangkat ini</span>
            </label>
        </div>

        <!-- Tombol Masuk -->
        <button type="submit" class="btn btn-primary btn-submit" id="btn-submit">
            <span id="btn-text">Masuk ke Sistem</span>
            <span id="btn-loading" style="display: none;">Memverifikasi...</span>
        </button>
    </form>

    <!-- Informasi Akun Bawaan Lingkungan Lokal -->
    <details class="demo-credentials-box">
        <summary>Informasi Akun Demo Lokal</summary>
        <ul class="demo-credentials-list">
            <li><strong>Admin:</strong> <code>admin</code> / <code>admin123</code> (Email: <code>admin@raplaundry.local</code>)</li>
            <li><strong>Kasir:</strong> <code>kasir</code> / <code>kasir123</code> (Email: <code>kasir@raplaundry.local</code>)</li>
        </ul>
    </details>

    <div class="auth-footer">
        <div class="offline-indicator">
            <span class="offline-dot"></span>
            <span>Sistem Operasional Offline-First</span>
        </div>
        <p style="margin-top: 0.5rem;">&copy; {{ date('Y') }} LaundryDesk — RAP Laundry. Seluruh hak cipta dilindungi.</p>
    </div>
</div>

<script>
    // Logika Vanilla JS Offline: Toggle visibilitas kata sandi
    (function () {
        const toggleBtn = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password');

        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', function () {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            });
        }

        // Mencegah double submission pada form login
        const form = document.getElementById('login-form');
        const submitBtn = document.getElementById('btn-submit');
        const btnText = document.getElementById('btn-text');
        const btnLoading = document.getElementById('btn-loading');

        if (form && submitBtn) {
            form.addEventListener('submit', function () {
                submitBtn.disabled = true;
                if (btnText && btnLoading) {
                    btnText.style.display = 'none';
                    btnLoading.style.display = 'inline';
                }
            });
        }
    })();
</script>
@endsection
