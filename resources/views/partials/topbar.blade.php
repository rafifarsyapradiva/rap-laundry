<header class="app-topbar">
    <div class="topbar-left">
        <button type="button" class="mobile-toggle-btn" aria-label="Buka Navigasi" onclick="window.LaundryDesk.toggleSidebar()">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        <h1 class="topbar-title">@yield('title', 'Dashboard')</h1>
    </div>

    <div class="topbar-right">
        @auth
            <div class="topbar-user">
                <div class="topbar-avatar" title="{{ auth()->user()->name }}">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div style="display: flex; flex-direction: column; gap: 2px;">
                    <span style="font-size: 0.875rem; font-weight: 600; color: var(--text-main); line-height: 1.2;">
                        {{ auth()->user()->name }}
                    </span>
                    <div>
                        <span class="badge {{ auth()->user()->isAdmin() ? 'badge-primary' : 'badge-info' }}" style="font-size: 0.6875rem; padding: 0.125rem 0.5rem;">
                            {{ auth()->user()->role->label() }}
                        </span>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm" id="btn-topbar-logout" title="Keluar dari sistem">
                    <x-icon name="arrow-right" size="14" />
                    <span>Keluar</span>
                </button>
            </form>
        @endauth
    </div>
</header>
