@props([
    'headers' => [],
])

<div class="table-container glass-card">
    <table {{ $attributes->merge(['class' => 'table-glass']) }}>
        @if (!empty($headers))
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
