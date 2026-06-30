@props([
    'label' => '',
    'value' => '0',
    'icon' => '',
    'color' => 'bg-indigo-50 text-indigo-600',
])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 bg-white p-5']) }}>
    <span class="flex h-11 w-11 items-center justify-center rounded-lg text-xl {{ $color }}">
        {{ $icon }}
    </span>
    <p class="mt-4 text-2xl font-bold text-gray-900">{{ $value }}</p>
    <p class="text-sm text-gray-500">{{ $label }}</p>
</div>
