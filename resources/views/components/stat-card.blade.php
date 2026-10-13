@props([
    'title' => null,
    'label' => null,
    'value',
    'icon' => 'dashboard',
    'hint' => null,
    'color' => 'primary',
])

@php
    $cardTitle = $title ?? $label ?? '';
@endphp

<div {{ $attributes->merge(['class' => 'stat-card glass-panel']) }}>
    <div class="stat-header">
        <span class="stat-label">{{ $cardTitle }}</span>
        <div class="stat-icon stat-icon-{{ $color }}">
            <x-icon :name="$icon" size="20" />
        </div>
    </div>
    <div class="stat-value">{{ $value }}</div>
    @if ($hint)
        <div class="stat-hint">{{ $hint }}</div>
    @endif
</div>
