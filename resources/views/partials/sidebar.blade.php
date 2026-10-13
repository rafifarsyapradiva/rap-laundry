<aside class="app-sidebar" id="app-sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">
            <x-icon name="dashboard" size="20" />
        </div>
        <div class="sidebar-brand-text">
            <span class="sidebar-brand-title">Laundry<span>Desk</span></span>
            <span class="sidebar-brand-subtitle">RAP Laundry System</span>
        </div>
    </div>

    <div class="sidebar-content">
        <!-- Section: Operasional -->
        <div>
            <div class="sidebar-section-title">Operasional</div>
            <ul class="sidebar-nav">
                <li>
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard*') ? 'active' : '' }}">
                        <x-icon name="dashboard" size="18" />
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="{{ Route::has('orders.index') ? route('orders.index') : (Route::has('orders.create') ? route('orders.create') : '#') }}" class="nav-link {{ request()->routeIs('orders*') ? 'active' : '' }}">
                        <x-icon name="orders" size="18" />
                        <span>Pesanan Cucian</span>
                    </a>
                </li>
                <li>
                    <a href="{{ Route::has('customers.index') ? route('customers.index') : '#' }}" class="nav-link {{ request()->routeIs('customers*') ? 'active' : '' }}">
                        <x-icon name="customers" size="18" />
                        <span>Pelanggan</span>
                    </a>
                </li>
                <li>
                    <a href="{{ Route::has('services.index') ? route('services.index') : '#' }}" class="nav-link {{ request()->routeIs('services*') ? 'active' : '' }}">
                        <x-icon name="services" size="18" />
                        <span>Layanan & Tarif</span>
                    </a>
                </li>
                <li>
                    <a href="{{ Route::has('payments.index') ? route('payments.index') : '#' }}" class="nav-link {{ request()->routeIs('payments*') ? 'active' : '' }}">
                        <x-icon name="payments" size="18" />
                        <span>Pembayaran</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Section: Manajemen (Admin Only) -->
        @if(auth()->check() && auth()->user()->isAdmin())
            <div>
                <div class="sidebar-section-title">Manajemen Pemilik</div>
                <ul class="sidebar-nav">
                    <li>
                        <a href="{{ Route::has('admin.reports.export') ? route('admin.reports.export') : '#' }}" class="nav-link {{ request()->routeIs('*reports*') ? 'active' : '' }}">
                            <x-icon name="reports" size="18" />
                            <span>Laporan Keuangan</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                            <x-icon name="users" size="18" />
                            <span>Manajemen Pengguna</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings*') ? 'active' : '' }}">
                            <x-icon name="settings" size="18" />
                            <span>Pengaturan Usaha</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.audit-logs.index') }}" class="nav-link {{ request()->routeIs('admin.audit-logs*') ? 'active' : '' }}">
                            <x-icon name="clock" size="18" />
                            <span>Log Audit Aktivitas</span>
                        </a>
                    </li>
                </ul>
            </div>
        @endif
    </div>

    <div class="sidebar-footer">
        <div class="offline-indicator">
            <span class="offline-dot"></span>
            <span>100% Offline Lokal</span>
        </div>
    </div>
</aside>

<!-- Backdrop overlay for mobile drawer -->
<div class="sidebar-backdrop" id="sidebar-backdrop" onclick="window.LaundryDesk.closeSidebar()"></div>
