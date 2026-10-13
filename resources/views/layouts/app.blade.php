<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — LaundryDesk (RAP Laundry)</title>

    <!-- Aset CSS 100% Offline-First (Tanpa CDN / Google Fonts) -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>
    <div class="app-shell">
        <!-- Sidebar Desktop & Mobile Off-Canvas Drawer -->
        @include('partials.sidebar')

        <!-- Area Konten Utama -->
        <div class="app-main">
            <!-- Topbar Navigasi & Info Pengguna -->
            @include('partials.topbar')

            <!-- Konten Halaman -->
            <main class="app-content" id="main-content">
                <!-- Flash Session & Validation Alerts -->
                @include('partials.alerts')

                @yield('content')
            </main>

            <!-- Footer Aplikasi -->
            <footer class="app-footer no-print">
                <p>&copy; {{ date('Y') }} LaundryDesk (RAP Laundry) — Aplikasi Kasir Laundry Offline-First</p>
            </footer>
        </div>
    </div>

    <!-- Modal Dialog Konfirmasi Tindakan Berisiko Reusable -->
    <x-confirm-modal id="confirm-modal" />

    <!-- Aset JS 100% Offline-First -->
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
