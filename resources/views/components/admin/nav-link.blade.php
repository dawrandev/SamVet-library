@props([
    'href' => '#',
    'active' => false,
    'icon' => null,
])

<a href="{{ $href }}"
   {{ $attributes->merge([
        'class' => 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition '
            . ($active
                ? 'bg-indigo-50 text-indigo-700'
                : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'),
   ]) }}>
    @if ($icon)
        <span class="text-lg">{{ $icon }}</span>
    @endif
    {{ $slot }}
</a>
