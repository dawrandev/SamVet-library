@props([
    'translatable' => false,
    'hasParent' => false,
    'parentLabel' => null,
])

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="max-w-full overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-800">
                    <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Nomi') }}</th>
                    @if ($hasParent)
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ $parentLabel ?? __('Ota kategoriya') }}</th>
                    @endif
                    @if ($translatable)
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Tarjima holati') }}</th>
                    @endif
                    <th class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Amallar') }}</th>
                </tr>
            </thead>
            <tbody>
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
