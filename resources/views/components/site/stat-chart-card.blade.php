@props([
    'heading',
    'rows',            // Collection<{label, count, share}>
    'type' => 'bar',   // 'bar' | 'donut'
    'centerLabel' => null, // donut only — label under the total in the middle
])

@php
    // Same palette the admin dashboard charts use, for visual consistency
    // across the whole app.
    $palette = ['#465fff', '#12b76a', '#f79009', '#f04438', '#9cb9ff', '#7592ff', '#32d583', '#fdb022'];
    $colors = $rows->map(fn ($row, $i) => $palette[$i % count($palette)])->values();
@endphp

<section class="rounded-2xl border border-gray-200 bg-white p-6">
    <h2 class="text-sm font-bold text-gray-900">{{ $heading }}</h2>

    @if ($rows->isEmpty())
        <p class="mt-4 text-sm text-gray-400">{{ __('Ma‘lumot yo‘q') }}</p>
    @else
        <div class="mt-4"
             @if ($type === 'donut')
                 data-chart-donut
                 data-labels="{{ $rows->pluck('label')->toJson() }}"
                 data-series="{{ $rows->pluck('count')->toJson() }}"
                 data-colors="{{ $colors->toJson() }}"
                 data-center="{{ $centerLabel }}"
             @else
                 data-chart-bar
                 data-labels="{{ $rows->pluck('label')->toJson() }}"
                 data-series="{{ $rows->pluck('count')->toJson() }}"
                 data-colors="{{ $colors->toJson() }}"
             @endif
        ></div>
    @endif
</section>
