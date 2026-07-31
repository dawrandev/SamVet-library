@extends('layouts.admin')

@section('title', __('Bosh sahifa'))

@section('content')
    @php
        // --- Donut: fund (copy status) ---
        $copyClr = ['available' => '#12b76a', 'borrowed' => '#465fff', 'lost' => '#f04438', 'written_off' => '#98a2b3'];
        $fundSeries = $fundLabels = $fundColors = [];
        foreach (\App\Enums\CopyStatus::cases() as $st) {
            $fundSeries[] = (int) ($copiesByStatus[$st->value] ?? 0);
            $fundLabels[] = $st->label();
            $fundColors[] = $copyClr[$st->value] ?? '#98a2b3';
        }

        // --- Donut: readers by type (only non-empty slices) ---
        $palette = ['#465fff', '#12b76a', '#f79009', '#f04438', '#9cb9ff', '#7592ff', '#32d583', '#fdb022'];
        $rdSeries = $rdLabels = $rdColors = [];
        foreach ($readerTypes as $t) {
            $c = (int) ($readersByType[$t->id] ?? 0);
            if ($c > 0) {
                $rdColors[] = $palette[count($rdSeries) % count($palette)];
                $rdSeries[] = $c;
                $rdLabels[] = $t->name;
            }
        }

        // --- Bar: copies by format (bosma/elektron/brayl/audio/video), toggled nusxa/nomi ---
        $formatClr = ['print' => '#465fff', 'electronic' => '#12b76a', 'braille' => '#f79009'];
        $fmtLabels = $fmtCopySeries = $fmtTitleSeries = $fmtColors = [];
        foreach (\App\Enums\BookFormat::cases() as $fmt) {
            $fmtLabels[] = $fmt->label();
            $fmtCopySeries[] = (int) ($copiesByFormat[$fmt->value] ?? 0);
            $fmtTitleSeries[] = (int) ($titlesByFormat[$fmt->value] ?? 0);
            $fmtColors[] = $formatClr[$fmt->value] ?? '#98a2b3';
        }
        // Audio/video aren't "copied" — a digitized track is one catalogued
        // item, so nomi and nusxa are the same number for these two.
        $fmtLabels[] = __('Audio');
        $fmtCopySeries[] = $fmtTitleSeries[] = $audiobooksTotal;
        $fmtColors[] = '#06aed4';
        $fmtLabels[] = __('Video');
        $fmtCopySeries[] = $fmtTitleSeries[] = $videosTotal;
        $fmtColors[] = '#7a5af8';

        // --- Bar: books by language, toggled between nusxa (copy) and nomi (title) counts ---
        // Same label set for both modes, so toggling never reshuffles categories.
        $langPalette = ['#465fff', '#12b76a', '#f79009', '#f04438', '#06aed4', '#7a5af8'];
        $langIds = collect($booksByLanguage->keys())->merge($copiesByLanguage->keys())->unique()->values();
        $langEntries = $langIds
            ->map(fn ($id) => [
                'label' => $languageNames[$id] ?? '—',
                'titles' => (int) ($booksByLanguage[$id] ?? 0),
                'copies' => (int) ($copiesByLanguage[$id] ?? 0),
            ])
            ->sortByDesc(fn ($e) => $e['titles'] + $e['copies'])
            ->values();
        $langLabels = $langCopySeries = $langTitleSeries = $langColors = [];
        foreach ($langEntries->take(count($langPalette)) as $i => $entry) {
            $langLabels[] = $entry['label'];
            $langCopySeries[] = $entry['copies'];
            $langTitleSeries[] = $entry['titles'];
            $langColors[] = $langPalette[$i];
        }
        if ($langEntries->count() > count($langPalette)) {
            $rest = $langEntries->slice(count($langPalette));
            $langLabels[] = __('Boshqa');
            $langCopySeries[] = $rest->sum('copies');
            $langTitleSeries[] = $rest->sum('titles');
            $langColors[] = '#98a2b3';
        }

        // --- Bar: books by top-level category (o‘quv-uslubiy/ilmiy/... — whatever
        // exists), toggled nusxa/nomi. Whatever top-level categories exist drives
        // this chart; nothing here is hardcoded to specific category names.
        $catPalette = ['#465fff', '#12b76a', '#f79009', '#f04438', '#06aed4', '#7a5af8'];
        $catLabels = $categoryStats['labels'];
        $catTitleSeries = $categoryStats['titles'];
        $catCopySeries = $categoryStats['copies'];
        $catColors = [];
        foreach ($catLabels as $i => $label) {
            $catColors[] = $catPalette[$i % count($catPalette)];
        }

        // --- Stacked bar: annual acquisition report (Yillik hisobot), by
        // category/format/language, toggled nusxa/nomi — 6 renderable
        // states total (3 dims x 2 modes). All aggregation already happened
        // in DashboardService::annualAcquisitionReport(); this block only
        // assigns colors and encodes for JS, never re-aggregates.
        $reportPalette = ['#465fff', '#12b76a', '#f79009', '#f04438', '#06aed4', '#7a5af8'];
        $reportDimensions = [];
        foreach (['format' => __('Shakli'), 'category' => __('Toifasi'), 'language' => __('Tili')] as $dim => $dimLabel) {
            $labels = $annualReport[$dim]['labels'];
            $otherIndex = $annualReport[$dim]['otherIndex'];
            $colors = [];
            foreach ($labels as $i => $label) {
                // Keyed by segment position, not by label text — a real
                // category/language could itself be named "Boshqa".
                $colors[] = $i === $otherIndex ? '#98a2b3' : $reportPalette[$i % count($reportPalette)];
            }
            $reportDimensions[$dim] = [
                'label' => $dimLabel,
                'labels' => $labels,
                'colors' => $colors,
                'copies' => $annualReport[$dim]['copies'],
                'titles' => $annualReport[$dim]['titles'],
            ];
        }

        // --- Line: daily usage (6 series, one shared count axis) ---
        $dailySeries = [
            ['name' => __('Berilgan kitoblar'), 'data' => $dailyUsage['loans'], 'color' => '#465fff'],
            ['name' => __('Qaytarilgan kitoblar'), 'data' => $dailyUsage['returns'], 'color' => '#7a5af8'],
            ['name' => __('Onlayn o‘qishlar'), 'data' => $dailyUsage['onlineReadings'], 'color' => '#12b76a'],
            ['name' => __('Kompyuterdan foydalanish'), 'data' => $dailyUsage['computerSessions'], 'color' => '#f79009', 'dashed' => true],
            ['name' => __('Tadbirlarda qatnashish'), 'data' => $dailyUsage['eventParticipations'], 'color' => '#f04438'],
            ['name' => __('Umumiy foydalanish'), 'data' => $dailyUsage['total'], 'color' => '#1d2939', 'dashed' => true],
        ];

        // --- Bar: reader demographics (gender / age / nationality) — bars, not donuts ---
        $otherLabels = [__('Boshqa'), __('Noma’lum')];
        $genderClr = ['Erkak' => '#465fff', 'Ayol' => '#f79009'];

        $genderLabels = $genderSeries = $genderColors = [];
        foreach ($readersByGender as $label => $count) {
            $genderLabels[] = $label;
            $genderSeries[] = $count;
            $genderColors[] = $genderClr[$label] ?? '#98a2b3';
        }

        $ageLabels = $ageSeries = $ageColors = [];
        foreach ($readersByAgeGroup as $label => $count) {
            $ageLabels[] = $label;
            $ageSeries[] = $count;
            $ageColors[] = in_array($label, $otherLabels, true) ? '#98a2b3' : $palette[count($ageColors) % count($palette)];
        }

        $natLabels = $natSeries = $natColors = [];
        foreach ($readersByNationality as $label => $count) {
            $natLabels[] = $label;
            $natSeries[] = $count;
            $natColors[] = in_array($label, $otherLabels, true) ? '#98a2b3' : $palette[count($natColors) % count($palette)];
        }
    @endphp

    <div data-dashboard>
        {{-- ===== Daily usage line chart =====
             Loan/overdue counts live on the "Berilgan kitoblar" page itself
             (and the always-visible header/sidebar badge) — not duplicated here. --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Kunlik statistika') }}</h3>
                    <p class="text-theme-xs mt-0.5 text-gray-400">{{ __('Berilgan kitoblar, onlayn o‘qishlar, kompyuterdan foydalanish, tadbir ishtiroki') }}</p>
                </div>
                <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-end gap-2">
                    <div>
                        <label class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">{{ __('Sanadan') }}</label>
                        <input type="date" name="stats_from" value="{{ $statsFrom->format('Y-m-d') }}"
                               class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-10 rounded-lg border border-gray-200 bg-transparent px-3 text-theme-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">{{ __('Sanagacha') }}</label>
                        <input type="date" name="stats_to" value="{{ $statsTo->format('Y-m-d') }}"
                               class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-10 rounded-lg border border-gray-200 bg-transparent px-3 text-theme-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90" />
                    </div>
                    <button type="submit" class="bg-brand-500 hover:bg-brand-600 h-10 rounded-lg px-4 text-theme-sm font-medium text-white transition">{{ __('Filtrlash') }}</button>
                </form>
            </div>
            <div id="chart-daily-usage" data-line class="mt-4"
                 data-dates="{{ json_encode($dailyUsage['dates']) }}" data-series="{{ json_encode($dailySeries) }}"></div>
        </div>

        {{-- ===== Foydalanuvchi statistikasi ===== --}}
        <h2 class="mt-8 text-lg font-bold text-gray-900 dark:text-white/90">{{ __('Foydalanuvchi statistikasi') }}</h2>
        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2 md:gap-5">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Foydalanuvchilar turi') }}</h3>
                <p class="text-theme-xs mt-0.5 text-gray-400">{{ __('Tur bo‘yicha taqsimot') }}</p>
                <div id="chart-readers" data-donut class="mt-1"
                     data-series="{{ json_encode($rdSeries) }}" data-labels="{{ json_encode($rdLabels) }}"
                     data-colors="{{ json_encode($rdColors) }}" data-center="{{ __('Kishi') }}"></div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Jinsi bo‘yicha') }}</h3>
                <p class="text-theme-xs mt-0.5 text-gray-400">{{ __('Foydalanuvchilar jinsi bo‘yicha taqsimot') }}</p>
                <div id="chart-gender" data-donut class="mt-1"
                     data-series="{{ json_encode($genderSeries) }}" data-labels="{{ json_encode($genderLabels) }}"
                     data-colors="{{ json_encode($genderColors) }}" data-center="{{ __('Kishi') }}"></div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Yoshi bo‘yicha') }}</h3>
                <p class="text-theme-xs mt-0.5 text-gray-400">{{ __('Foydalanuvchilar yosh guruhi bo‘yicha taqsimot') }}</p>
                <div id="chart-age" data-bar class="mt-1"
                     data-labels="{{ json_encode($ageLabels) }}" data-colors="{{ json_encode($ageColors) }}"
                     data-series-copies="{{ json_encode($ageSeries) }}" data-label-copies="{{ __('Kishi') }}"></div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Millati bo‘yicha') }}</h3>
                <p class="text-theme-xs mt-0.5 text-gray-400">{{ __('Foydalanuvchilar millati bo‘yicha taqsimot') }}</p>
                <div id="chart-nationality" data-bar class="mt-1"
                     data-labels="{{ json_encode($natLabels) }}" data-colors="{{ json_encode($natColors) }}"
                     data-series-copies="{{ json_encode($natSeries) }}" data-label-copies="{{ __('Kishi') }}"></div>
            </div>
        </div>

        {{-- ===== Fond statistikasi ===== --}}
        <h2 class="mt-8 text-lg font-bold text-gray-900 dark:text-white/90">{{ __('Fond statistikasi') }}</h2>
        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2 md:gap-5">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Fond holati') }}</h3>
                <p class="text-theme-xs mt-0.5 text-gray-400">{{ __('Nusxalar holat bo‘yicha') }}</p>
                <div id="chart-fund" data-donut class="mt-1"
                     data-series="{{ json_encode($fundSeries) }}" data-labels="{{ json_encode($fundLabels) }}"
                     data-colors="{{ json_encode($fundColors) }}" data-center="{{ __('Nusxa') }}"></div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Shakli bo‘yicha') }}</h3>
                        <p class="text-theme-xs mt-0.5 text-gray-400">{{ __('Bosma, elektron, brayl, audio, video') }}</p>
                    </div>
                    <div class="inline-flex rounded-lg border border-gray-200 p-0.5 dark:border-gray-800" data-bar-toggle-group>
                        <button type="button" data-bar-mode="copies" class="rounded-md bg-brand-500 px-3 py-1.5 text-theme-xs font-medium text-white transition">{{ __('Nusxa') }}</button>
                        <button type="button" data-bar-mode="titles" class="rounded-md px-3 py-1.5 text-theme-xs font-medium text-gray-500 transition dark:text-gray-400">{{ __('Nomi') }}</button>
                    </div>
                </div>
                <div id="chart-format" data-bar class="mt-1"
                     data-labels="{{ json_encode($fmtLabels) }}" data-colors="{{ json_encode($fmtColors) }}"
                     data-series-copies="{{ json_encode($fmtCopySeries) }}" data-series-titles="{{ json_encode($fmtTitleSeries) }}"
                     data-label-copies="{{ __('Nusxa') }}" data-label-titles="{{ __('Nomi') }}"></div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Tillar bo‘yicha') }}</h3>
                        <p class="text-theme-xs mt-0.5 text-gray-400">{{ __('Kitoblar tili bo‘yicha taqsimot') }}</p>
                    </div>
                    <div class="inline-flex rounded-lg border border-gray-200 p-0.5 dark:border-gray-800" data-bar-toggle-group>
                        <button type="button" data-bar-mode="copies" class="rounded-md bg-brand-500 px-3 py-1.5 text-theme-xs font-medium text-white transition">{{ __('Nusxa') }}</button>
                        <button type="button" data-bar-mode="titles" class="rounded-md px-3 py-1.5 text-theme-xs font-medium text-gray-500 transition dark:text-gray-400">{{ __('Nomi') }}</button>
                    </div>
                </div>
                <div id="chart-language-bar" data-bar class="mt-1"
                     data-labels="{{ json_encode($langLabels) }}" data-colors="{{ json_encode($langColors) }}"
                     data-series-copies="{{ json_encode($langCopySeries) }}" data-series-titles="{{ json_encode($langTitleSeries) }}"
                     data-label-copies="{{ __('Nusxa') }}" data-label-titles="{{ __('Nomi') }}"></div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Asosiy toifalar bo‘yicha') }}</h3>
                        <p class="text-theme-xs mt-0.5 text-gray-400">{{ __('O‘quv-uslubiy, ilmiy, xorijiy, badiiy adabiyotlar') }}</p>
                    </div>
                    <div class="inline-flex rounded-lg border border-gray-200 p-0.5 dark:border-gray-800" data-bar-toggle-group>
                        <button type="button" data-bar-mode="copies" class="rounded-md bg-brand-500 px-3 py-1.5 text-theme-xs font-medium text-white transition">{{ __('Nusxa') }}</button>
                        <button type="button" data-bar-mode="titles" class="rounded-md px-3 py-1.5 text-theme-xs font-medium text-gray-500 transition dark:text-gray-400">{{ __('Nomi') }}</button>
                    </div>
                </div>
                <div id="chart-category-bar" data-bar class="mt-1"
                     data-labels="{{ json_encode($catLabels) }}" data-colors="{{ json_encode($catColors) }}"
                     data-series-copies="{{ json_encode($catCopySeries) }}" data-series-titles="{{ json_encode($catTitleSeries) }}"
                     data-label-copies="{{ __('Nusxa') }}" data-label-titles="{{ __('Nomi') }}"></div>
            </div>
        </div>

        {{-- ===== Annual acquisition report (Yillik hisobot) ===== --}}
        <div class="mt-5 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Yillik hisobot') }}</h3>
                    <p class="text-theme-xs mt-0.5 text-gray-400">{{ __('Kirish akti sanasi bo‘yicha qabul qilingan resurslar') }}</p>
                </div>
                @if (count($annualReport['years']))
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="inline-flex rounded-lg border border-gray-200 p-0.5 dark:border-gray-800" data-stacked-bar-dimension-group>
                            <button type="button" data-stacked-bar-dimension="format" class="rounded-md bg-brand-500 px-3 py-1.5 text-theme-xs font-medium text-white transition">{{ __('Shakli') }}</button>
                            <button type="button" data-stacked-bar-dimension="category" class="rounded-md px-3 py-1.5 text-theme-xs font-medium text-gray-500 transition dark:text-gray-400">{{ __('Toifasi') }}</button>
                            <button type="button" data-stacked-bar-dimension="language" class="rounded-md px-3 py-1.5 text-theme-xs font-medium text-gray-500 transition dark:text-gray-400">{{ __('Tili') }}</button>
                        </div>
                        <div class="inline-flex rounded-lg border border-gray-200 p-0.5 dark:border-gray-800" data-bar-toggle-group>
                            <button type="button" data-bar-mode="copies" class="rounded-md bg-brand-500 px-3 py-1.5 text-theme-xs font-medium text-white transition">{{ __('Nusxa') }}</button>
                            <button type="button" data-bar-mode="titles" class="rounded-md px-3 py-1.5 text-theme-xs font-medium text-gray-500 transition dark:text-gray-400">{{ __('Nomi') }}</button>
                        </div>
                    </div>
                @endif
            </div>
            @if (count($annualReport['years']))
                <div id="chart-annual-report" data-stacked-bar class="mt-4"
                     data-years="{{ json_encode($annualReport['years']) }}"
                     data-dimensions="{{ json_encode($reportDimensions) }}"
                     data-default-dimension="format"></div>
            @else
                <p class="py-10 text-center text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Hali kirish akti sanasi kiritilgan kitob nusxalari yo‘q.') }}</p>
            @endif
        </div>

        {{-- ===== Online readings filter + quick counts ===== --}}
        <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3 md:gap-5">
            <div class="lg:col-span-2 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex flex-col gap-3 px-5 py-4 sm:px-6">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Onlayn o‘qishlar') }}</h3>
                        <span class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Jami') }}: {{ number_format($onlineReadings->total(), 0, '.', ' ') }}</span>
                    </div>

                    {{-- Date/time range filter --}}
                    <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div class="flex-1">
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Vaqtdan') }}</label>
                            <input type="datetime-local" name="from" value="{{ $onlineReadingsFrom->format('Y-m-d\TH:i') }}"
                                   class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90" />
                        </div>
                        <div class="flex-1">
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Vaqtgacha') }}</label>
                            <input type="datetime-local" name="to" value="{{ $onlineReadingsTo->format('Y-m-d\TH:i') }}"
                                   class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90" />
                        </div>
                        <button type="submit" class="bg-brand-500 hover:bg-brand-600 h-11 rounded-lg px-5 text-sm font-medium text-white transition">{{ __('Filtrlash') }}</button>
                    </form>
                </div>
                <div class="max-w-full overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-y border-gray-100 dark:border-gray-800">
                                <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400 sm:px-6">{{ __('Foydalanuvchi') }}</th>
                                <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Kitob') }}</th>
                                <th class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400 sm:px-6">{{ __('O‘qilgan vaqti') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($onlineReadings as $reading)
                                <tr class="border-b border-gray-50 last:border-0 dark:border-gray-800/60">
                                    <td class="px-5 py-3.5 text-theme-sm font-medium text-gray-800 dark:text-white/90 sm:px-6">{{ $reading->reader?->full_name ?? '—' }}</td>
                                    <td class="px-5 py-3.5 text-theme-sm text-gray-600 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($reading->book?->title ?? '—', 30) }}</td>
                                    <td class="px-5 py-3.5 text-right text-theme-sm text-gray-600 dark:text-gray-400 sm:px-6">{{ $reading->read_at->format('d.m.Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-10 text-center">
                                        <x-admin.icon name="book" class="mx-auto h-9 w-9 text-gray-300 dark:text-gray-600" />
                                        <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Bu oraliqda onlayn o‘qish topilmadi.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($onlineReadings->hasPages())
                    <div class="px-5 py-4 sm:px-6">
                        {{ $onlineReadings->links() }}
                    </div>
                @endif
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Qisqa hisobot') }}</h3>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    @php
                        $counts = [
                            ['newspaper', __('Jurnallar'), $journalsTotal],
                            ['document-text', __('Maqolalar'), $articlesTotal],
                            ['newspaper', __('Yangiliklar'), $newsTotal],
                            ['computer-desktop', __('Kompyuterlar'), $computersTotal],
                            ['clipboard-list', __('Obuna :year', ['year' => $subscriptionYear]), $subscribersThisYear],
                            ['folder', __('Kategoriyalar'), $categoriesTotal],
                        ];
                    @endphp
                    @foreach ($counts as [$icon, $label, $value])
                        <div class="rounded-xl border border-gray-100 p-3 dark:border-gray-800">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400"><x-admin.icon :name="$icon" class="h-4 w-4" /></span>
                            <p class="mt-2 text-lg font-bold text-gray-800 dark:text-white/90">{{ number_format($value, 0, '.', ' ') }}</p>
                            <p class="text-theme-xs text-gray-400">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
