@extends('layouts.site')

@section('title', __('Statistika'))

@php
    $n = fn (int $value): string => number_format($value, 0, '.', ' ');

    // Headline figures (live) shown in the navy band.
    $headline = [
        [__('Jami nusxalar'), $n($totals['copies'])],
        [__('Kitob nomlari'), $n($totals['titles'])],
        [__('Foydalanuvchilar'), $n($totals['readers'])],
        [__('Maqolalar'), $n($totals['articles'])],
    ];

    // Secondary figures: live counts only.
    $secondary = [
        [__('Jurnallar'), $n($totals['journals'])],
        [__('Gazetalar'), $n($totals['newspapers'])],
        [__('Jurnal sonlari'), $n($totals['issues'])],
        [__('Yangiliklar'), $n($totals['news'])],
    ];

    $fundBreakdowns = [
        [__('Kategoriyalar bo‘yicha'), $byCategory, 'bar'],
        [__('Shakli bo‘yicha'), $byFormat, 'bar'],
        [__('Kitoblar turi bo‘yicha'), $byType, 'bar'],
        [__('Kitoblar tili bo‘yicha'), $byLanguage, 'donut'],
    ];

    $readerBreakdowns = [
        [__('Turi bo‘yicha'), $readersByType, 'donut'],
        [__('Jinsi bo‘yicha'), $readersByGender, 'donut'],
        [__('Yoshi bo‘yicha'), $readersByAgeGroup, 'bar'],
    ];
@endphp

@section('content')
    <div data-statistics-charts class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
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

        {{-- Fund breakdowns (kategoriya / shakli / turi / til) --}}
        <h2 class="mt-12 text-xl font-bold tracking-tight text-gray-900">{{ __('Fond statistikasi') }}</h2>
        <div class="mt-5 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($fundBreakdowns as [$heading, $rows, $type])
                <x-site.stat-chart-card :heading="$heading" :rows="$rows" :type="$type" />
            @endforeach
        </div>

        {{-- Reader demographics (turi / jinsi / yoshi) --}}
        <h2 class="mt-12 text-xl font-bold tracking-tight text-gray-900">{{ __('Foydalanuvchilar statistikasi') }}</h2>
        <div class="mt-5 grid gap-6 lg:grid-cols-3">
            @foreach ($readerBreakdowns as [$heading, $rows, $type])
                <x-site.stat-chart-card :heading="$heading" :rows="$rows" :type="$type" :center-label="__('Kishi')" />
            @endforeach
        </div>

        {{-- Kafedralar kesimida ta'minganlik darajasi --}}
        <h2 class="mt-12 text-xl font-bold tracking-tight text-gray-900">{{ __('Kafedralar kesimida ta’minganlik darajasi') }}</h2>
        <div class="mt-5 grid gap-6">
            <x-site.stat-breakdown-card :heading="__('Kafedralar bo‘yicha')" :rows="$byDepartment" unit="%" />
        </div>
    </div>
@endsection
