@props([
    'status',
    'type' => null,
])

@php
    $label = '';
    $color = 'neutral';

    if ($status instanceof \App\Enums\LaundryStatus) {
        $label = $status->label();
        $color = $status->color();
    } elseif ($status instanceof \App\Enums\PaymentStatus) {
        $label = $status->label();
        $color = $status->color();
    } elseif (is_string($status)) {
        $laundryEnum = \App\Enums\LaundryStatus::tryFrom($status);
        if ($laundryEnum) {
            $label = $laundryEnum->label();
            $color = $laundryEnum->color();
        } else {
            $paymentEnum = \App\Enums\PaymentStatus::tryFrom($status);
            if ($paymentEnum) {
                $label = $paymentEnum->label();
                $color = $paymentEnum->color();
            } else {
                $label = ucfirst(str_replace('_', ' ', $status));
                $color = 'neutral';
            }
        }
    }

    $badgeClass = match ($color) {
        'success' => 'badge-success',
        'warning' => 'badge-warning',
        'danger' => 'badge-danger',
        'info' => 'badge-info',
        'primary' => 'badge-primary',
        default => 'badge-neutral',
    };
@endphp

<span {{ $attributes->merge(['class' => "badge {$badgeClass}"]) }}>
    {{ $label }}
</span>
