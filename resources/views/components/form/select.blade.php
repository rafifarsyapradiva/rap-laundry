@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'hint' => null,
    'id' => null,
])

@php
    $selectId = $id ?? $name;
    $hasError = isset($errors) && $errors->has($name);
    $currentValue = old($name, $selected);
@endphp

<div class="form-group">
    @if ($label)
        <label for="{{ $selectId }}" class="form-label">
            {{ $label }}
            @if ($required)
                <span style="color: var(--status-danger);">*</span>
            @endif
        </label>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $selectId }}"
        @if ($required) required @endif
        @if ($disabled) disabled @endif
        {{ $attributes->merge(['class' => 'form-select' . ($hasError ? ' is-invalid' : '')]) }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @if (!empty($options))
            @foreach ($options as $val => $optLabel)
                <option value="{{ $val }}" {{ (string) $currentValue === (string) $val ? 'selected' : '' }}>
                    {{ $optLabel }}
                </option>
            @endforeach
        @endif

        {{ $slot }}
    </select>

    @if ($hasError)
        <div class="form-error">{{ $errors->first($name) }}</div>
    @elseif ($hint)
        <div class="form-hint">{{ $hint }}</div>
    @endif
</div>
