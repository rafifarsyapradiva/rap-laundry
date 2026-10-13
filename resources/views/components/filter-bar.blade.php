@props([
    'action' => null,
    'method' => 'GET',
])

@if ($action)
    <form action="{{ $action }}" method="{{ $method }}" {{ $attributes->merge(['class' => 'filter-bar glass-panel']) }}>
        {{ $slot }}
    </form>
@else
    <div {{ $attributes->merge(['class' => 'filter-bar glass-panel']) }}>
        {{ $slot }}
    </div>
@endif
