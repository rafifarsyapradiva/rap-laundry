@props([
    'name',
    'label' => null,
    'value' => null,
    'rows' => 3,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'hint' => null,
    'id' => null,
])

@php
    $textareaId = $id ?? $name;
    $hasError = isset($errors) && $errors->has($name);
@endphp

<div class="form-group">
    @if ($label)
        <label for="{{ $textareaId }}" class="form-label">
            {{ $label }}
            @if ($required)
                <span style="color: var(--status-danger);">*</span>
            @endif
        </label>
    @endif

    <textarea
        name="{{ $name }}"
        id="{{ $textareaId }}"
        rows="{{ $rows }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($required) required @endif
        @if ($disabled) disabled @endif
        {{ $attributes->merge(['class' => 'form-textarea' . ($hasError ? ' is-invalid' : '')]) }}
    >{{ old($name, $value) }}</textarea>

    @if ($hasError)
        <div class="form-error">{{ $errors->first($name) }}</div>
    @elseif ($hint)
        <div class="form-hint">{{ $hint }}</div>
    @endif
</div>
