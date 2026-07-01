@props([
    'title' => null,
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6']) }}>
    @if ($title)
        <div class="mb-5">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h3>
            @if ($description)
                <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">{{ $description }}</p>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
