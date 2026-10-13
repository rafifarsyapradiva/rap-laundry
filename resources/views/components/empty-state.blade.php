@props([
    'title' => 'Tidak Ada Data',
    'description' => 'Belum ada data yang dapat ditampilkan saat ini.',
    'icon' => 'orders',
    'actionText' => null,
    'actionUrl' => null,
    'actionIcon' => 'plus',
])

<div {{ $attributes->merge(['class' => 'empty-state glass-panel']) }}>
    <div class="empty-state-icon">
        <x-icon :name="$icon" size="32" />
    </div>
    <h3 class="empty-state-title">{{ $title }}</h3>
    @if ($description)
        <p class="empty-state-desc">{{ $description }}</p>
    @endif
    @if ($actionText && $actionUrl)
        <div style="margin-top: var(--space-2);">
            <x-button :href="$actionUrl" variant="primary" :icon="$actionIcon">
                {{ $actionText }}
            </x-button>
        </div>
    @endif
</div>
