@extends('layouts.site')

@section('title', __('Statistika'))

@php
    $n = fn (int $value): string => number_format($value, 0, '.', ' ');

    // Headline figures (live) shown in the navy band.
    $headline = [
        [__('Jami nusxalar'), $n($totals['copies'])],
        [__('Kitob nomlari'), $n($totals['titles'])],
        [__('Foydalanuvchilar'), $n($totals['readers'])],
        [__('Mualliflar'), $n($totals['authors'])],
    ];

    // Secondary figures: live counts + the two config-driven facts.
    $secondary = [
        [__('Jurnallar'), $n($totals['journals'])],
        [__('Gazetalar'), $n($totals['newspapers'])],
        [__('Jurnal sonlari'), $n($totals['issues'])],
        [__('Maqolalar'), $n($totals['articles'])],
        [__('Yangiliklar'), $n($totals['news'])],
        [__('Tashkil etilgan'), (string) $facts['founded_year']],
        [__('O‘qish zali o‘rni'), $n($facts['reading_room_seats'])],
    ];

    $breakdowns = [
        [__('Kitoblar turi bo‘yicha'), $byType],
        [__('Kitoblar tili bo‘yicha'), $byLanguage],
        [__('Kategoriyalar bo‘yicha'), $byCategory],
    ];
@endphp

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <nav class="text-sm text-gray-500">
            <a href="{{ route('home') }}" class="hover:text-blue-700">{{ __('Bosh sahifa') }}</a>
            <span class="mx-1.5 text-gray-300">/</span>
            <span class="text-gray-700">{{ __('Statistika') }}</span>
        </nav>

        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-gray-900">{{ __('Statistika') }}</h1>
        <p class="mt-1.5 text-sm text-gray-500">{{ __('Axborot resurs markazi fondi va foydalanish ko‘rsatkichlari.') }}</p>

        {{-- Headline band --}}
        <div class="mt-7 grid gap-px overflow-hidden rounded-2xl bg-blue-800 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($headline as [$label, $value])
                <div class="bg-blue-900 p-6">
                    <p class="text-3xl font-extrabold text-white">{{ $value }}</p>
                    <p class="mt-1 text-sm text-blue-100/70">{{ $label }}</p>
                </div>
            @endforeach
        </div>

        {{-- Secondary tiles --}}
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($secondary as [$label, $value])
                <div class="rounded-2xl border border-gray-200 bg-white p-5">
                    <p class="text-2xl font-bold text-gray-900">{{ $value }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ $label }}</p>
                </div>
            @endforeach
        </div>

        {{-- Breakdowns --}}
        <div class="mt-10 grid gap-6 lg:grid-cols-3">
            @foreach ($breakdowns as [$heading, $rows])
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
            @endforeach
        </div>
    </div>
@endsection
