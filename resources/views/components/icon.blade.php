@props([
    'name',
    'size' => 20,
    'class' => '',
])

@php
    $iconPath = public_path('icons/' . $name . '.svg');
@endphp

@if (file_exists($iconPath))
    {!! str_replace(
        '<svg ',
        '<svg class="icon ' . e($class) . '" width="' . e((string) $size) . '" height="' . e((string) $size) . '" ',
        file_get_contents($iconPath)
    ) !!}
@else
    <span class="icon-fallback {{ $class }}" style="display:inline-block;width:{{ $size }}px;height:{{ $size }}px;" aria-hidden="true"></span>
@endif
