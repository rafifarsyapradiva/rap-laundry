@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'iconPosition' => 'left',
    'disabled' => false,
    'href' => null,
])

@php
    $sizeClass = match ($size) {
        'sm' => 'btn-sm',
        'lg' => 'btn-lg',
        default => '',
    };

    $variantClass = match ($variant) {
        'secondary' => 'btn-secondary',
        'success' => 'btn-success',
        'danger' => 'btn-danger',
        'outline' => 'btn-outline',
        default => 'btn-primary',
    };

    $classes = trim("btn {$variantClass} {$sizeClass}");
    $iconSize = match ($size) {
        'sm' => 14,
        'lg' => 18,
        default => 16,
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon && $iconPosition === 'left')
            <x-icon :name="$icon" :size="$iconSize" />
        @endif
        <span>{{ $slot }}</span>
        @if ($icon && $iconPosition === 'right')
            <x-icon :name="$icon" :size="$iconSize" />
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon && $iconPosition === 'left')
            <x-icon :name="$icon" :size="$iconSize" />
        @endif
        <span>{{ $slot }}</span>
        @if ($icon && $iconPosition === 'right')
            <x-icon :name="$icon" :size="$iconSize" />
        @endif
    </button>
@endif
