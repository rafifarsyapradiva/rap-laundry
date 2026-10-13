@if (session('success'))
    <div class="alert alert-success" role="alert">
        <div class="alert-content">
            <x-icon name="check" size="18" class="icon-success" />
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" class="alert-close" aria-label="Tutup" onclick="window.LaundryDesk.dismissAlert(this)">
            <x-icon name="x" size="16" />
        </button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger" role="alert">
        <div class="alert-content">
            <x-icon name="alert-triangle" size="18" class="icon-danger" />
            <span>{{ session('error') }}</span>
        </div>
        <button type="button" class="alert-close" aria-label="Tutup" onclick="window.LaundryDesk.dismissAlert(this)">
            <x-icon name="x" size="16" />
        </button>
    </div>
@endif

@if (session('warning'))
    <div class="alert alert-warning" role="alert">
        <div class="alert-content">
            <x-icon name="alert-triangle" size="18" class="icon-warning" />
            <span>{{ session('warning') }}</span>
        </div>
        <button type="button" class="alert-close" aria-label="Tutup" onclick="window.LaundryDesk.dismissAlert(this)">
            <x-icon name="x" size="16" />
        </button>
    </div>
@endif

@if (session('info'))
    <div class="alert alert-info" role="alert">
        <div class="alert-content">
            <x-icon name="info" size="18" class="icon-primary" />
            <span>{{ session('info') }}</span>
        </div>
        <button type="button" class="alert-close" aria-label="Tutup" onclick="window.LaundryDesk.dismissAlert(this)">
            <x-icon name="x" size="16" />
        </button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <div class="alert-content">
            <x-icon name="alert-triangle" size="18" class="icon-danger" />
            <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                <span style="font-weight: 600;">Terjadi beberapa kesalahan:</span>
                <ul style="margin: 0; padding-left: 1.25rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <button type="button" class="alert-close" aria-label="Tutup" onclick="window.LaundryDesk.dismissAlert(this)">
            <x-icon name="x" size="16" />
        </button>
    </div>
@endif
