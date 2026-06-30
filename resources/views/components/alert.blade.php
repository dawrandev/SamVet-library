@props([
    'type' => 'info', // info | success | error | warning
])

@php
    $styles = [
        'info'    => 'border-blue-200 bg-blue-50 text-blue-700',
        'success' => 'border-green-200 bg-green-50 text-green-700',
        'error'   => 'border-red-200 bg-red-50 text-red-700',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg border px-4 py-3 text-sm ' . ($styles[$type] ?? $styles['info'])]) }}>
    {{ $slot }}
</div>
