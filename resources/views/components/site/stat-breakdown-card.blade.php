@props([
    'heading',
    'rows', // Collection<{label, count, share}>
])

@php
    $n = fn (int $value): string => number_format($value, 0, '.', ' ');
@endphp

<section class="rounded-2xl border border-gray-200 bg-white p-6">
    <h2 class="text-sm font-bold text-gray-900">{{ $heading }}</h2>

    @if ($rows->isEmpty())
        <p class="mt-4 text-sm text-gray-400">{{ __('Ma‘lumot yo‘q') }}</p>
    @else
        <div class="mt-5 space-y-4">
            @foreach ($rows as $row)
                <div>
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <span class="line-clamp-1 text-gray-700">{{ $row['label'] }}</span>
                        <span class="flex-none tabular-nums text-gray-400">{{ $n($row['count']) }}</span>
                    </div>
                    <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-blue-600" style="width: {{ $row['share'] }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
