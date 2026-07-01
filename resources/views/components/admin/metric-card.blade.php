@props([
    'label' => '',
    'value' => '0',
    'icon' => '📊',
    'trend' => null,   // masalan '11.01%'
    'up' => true,      // trend yo'nalishi
])

<div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 text-2xl dark:bg-gray-800">
        {{ $icon }}
    </div>

    <div class="mt-5 flex items-end justify-between">
        <div>
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</span>
            <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">{{ $value }}</h4>
        </div>

        @if ($trend)
            <span @class([
                'flex items-center gap-1 rounded-full py-0.5 pl-2 pr-2.5 text-sm font-medium',
                'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500' => $up,
                'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500' => ! $up,
            ])>
                {{ $up ? '▲' : '▼' }} {{ $trend }}
            </span>
        @endif
    </div>
</div>
