@props([
    'heading',
    'years',       // array<int>
    'dimensions',  // ['format' => [...], 'category' => [...], 'language' => [...]]
])

<section class="rounded-2xl border border-gray-200 bg-white p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-sm font-bold text-gray-900">{{ $heading }}</h2>

        @if (count($years))
            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex rounded-lg border border-gray-200 p-0.5" data-stacked-bar-dimension-group>
                    <button type="button" data-stacked-bar-dimension="format" class="rounded-md bg-blue-700 px-3 py-1.5 text-xs font-medium text-white transition">{{ __('Shakli') }}</button>
                    <button type="button" data-stacked-bar-dimension="category" class="rounded-md px-3 py-1.5 text-xs font-medium text-gray-500 transition">{{ __('Toifasi') }}</button>
                    <button type="button" data-stacked-bar-dimension="language" class="rounded-md px-3 py-1.5 text-xs font-medium text-gray-500 transition">{{ __('Tili') }}</button>
                </div>
                <div class="inline-flex rounded-lg border border-gray-200 p-0.5" data-bar-toggle-group>
                    <button type="button" data-bar-mode="copies" class="rounded-md bg-blue-700 px-3 py-1.5 text-xs font-medium text-white transition">{{ __('Nusxa') }}</button>
                    <button type="button" data-bar-mode="titles" class="rounded-md px-3 py-1.5 text-xs font-medium text-gray-500 transition">{{ __('Nomi') }}</button>
                </div>
            </div>
        @endif
    </div>

    @if (count($years))
        <div id="chart-site-annual-report" data-chart-stacked-bar class="mt-5"
             data-years="{{ json_encode($years) }}"
             data-dimensions="{{ json_encode($dimensions) }}"
             data-default-dimension="format"></div>
    @else
        <p class="mt-4 text-sm text-gray-400">{{ __('Ma‘lumot yo‘q') }}</p>
    @endif
</section>
