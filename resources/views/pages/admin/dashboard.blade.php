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
        foreach (\App\Enums\ReaderType::cases() as $t) {
            $c = (int) ($readersByType[$t->value] ?? 0);
            if ($c > 0) {
                $rdColors[] = $palette[count($rdSeries) % count($palette)];
                $rdSeries[] = $c;
                $rdLabels[] = $t->label();
            }
        }

        // --- Donut: computers by status ---
        $compClr = ['working' => '#12b76a', 'broken' => '#f04438', 'in_repair' => '#f79009'];
        $compSeries = $compLabels = $compColors = [];
        foreach (\App\Enums\ComputerStatus::cases() as $st) {
            $compSeries[] = (int) ($computersByStatus[$st->value] ?? 0);
            $compLabels[] = $st->label();
            $compColors[] = $compClr[$st->value] ?? '#98a2b3';
        }
    @endphp

    <div data-dashboard>
        {{-- ===== KPI cards ===== --}}
        <div class="grid grid-cols-2 gap-4 xl:grid-cols-4 md:gap-5">
            <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
                <div class="flex items-center gap-4">
                    <span class="flex h-11 w-11 flex-none items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400"><x-admin.icon name="book" class="h-5 w-5" /></span>
                    <div class="min-w-0">
                        <h4 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ number_format($booksTotal, 0, '.', ' ') }}</h4>
                        <span class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Kitob nomi') }}</span>
                    </div>
                </div>
                <p class="text-theme-xs mt-3 text-gray-400">{{ number_format($copiesTotal, 0, '.', ' ') }} {{ __('nusxa') }} · {{ number_format($copiesAvailable, 0, '.', ' ') }} {{ __('mavjud') }}</p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
                <div class="flex items-center gap-4">
                    <span class="flex h-11 w-11 flex-none items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400"><x-admin.icon name="users" class="h-5 w-5" /></span>
                    <div class="min-w-0">
                        <h4 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ number_format($readersTotal, 0, '.', ' ') }}</h4>
                        <span class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Foydalanuvchi') }}</span>
                    </div>
                </div>
                <p class="text-theme-xs mt-3 text-success-600 dark:text-success-500">{{ number_format($readersActive, 0, '.', ' ') }} {{ __('faol') }}</p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
                <div class="flex items-center gap-4">
                    <span class="flex h-11 w-11 flex-none items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400"><x-admin.icon name="book" class="h-5 w-5" /></span>
                    <div class="min-w-0">
                        <h4 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ number_format($loansActive, 0, '.', ' ') }}</h4>
                        <span class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Hozir berilgan') }}</span>
                    </div>
                </div>
                <p class="text-theme-xs mt-3 text-gray-400">{{ __('foydalanuvchilarda') }}</p>
            </div>

            <a href="{{ route('admin.loans.index', ['scope' => 'overdue']) }}"
               class="block rounded-2xl border border-gray-200 bg-white p-4 transition hover:border-error-300 dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-error-500/40 sm:p-5">
                <div class="flex items-center gap-4">
                    <span class="flex h-11 w-11 flex-none items-center justify-center rounded-xl bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500"><x-admin.icon name="clock" class="h-5 w-5" /></span>
                    <div class="min-w-0">
                        <h4 class="text-xl font-bold {{ $overdue > 0 ? 'text-error-600 dark:text-error-500' : 'text-gray-800 dark:text-white/90' }}">{{ number_format($overdue, 0, '.', ' ') }}</h4>
                        <span class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Muddati o‘tgan') }}</span>
                    </div>
                </div>
                <p class="text-theme-xs mt-3 text-gray-400">{{ __('ko‘rish uchun bosing') }}</p>
            </a>
        </div>

        {{-- ===== Donut charts ===== --}}
        <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3 md:gap-5">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Fond holati') }}</h3>
                <p class="text-theme-xs mt-0.5 text-gray-400">{{ __('Nusxalar holat bo‘yicha') }}</p>
                <div id="chart-fund" data-donut class="mt-1"
                     data-series="{{ json_encode($fundSeries) }}" data-labels="{{ json_encode($fundLabels) }}"
                     data-colors="{{ json_encode($fundColors) }}" data-center="{{ __('Nusxa') }}"></div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Foydalanuvchilar turi') }}</h3>
                <p class="text-theme-xs mt-0.5 text-gray-400">{{ __('Tur bo‘yicha taqsimot') }}</p>
                <div id="chart-readers" data-donut class="mt-1"
                     data-series="{{ json_encode($rdSeries) }}" data-labels="{{ json_encode($rdLabels) }}"
                     data-colors="{{ json_encode($rdColors) }}" data-center="{{ __('Kishi') }}"></div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Kompyuterlar holati') }}</h3>
                <p class="text-theme-xs mt-0.5 text-gray-400">{{ __('Elektron o‘qish zali') }}</p>
                <div id="chart-computers" data-donut class="mt-1"
                     data-series="{{ json_encode($compSeries) }}" data-labels="{{ json_encode($compLabels) }}"
                     data-colors="{{ json_encode($compColors) }}" data-center="{{ __('Dona') }}"></div>
            </div>
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
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Kimdan') }}</label>
                            <input type="datetime-local" name="from" value="{{ $onlineReadingsFrom->format('Y-m-d\TH:i') }}"
                                   class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90" />
                        </div>
                        <div class="flex-1">
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Kimgacha') }}</label>
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
                            ['clipboard-list', __('Obunalar'), $subscriptionsTotal],
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
                <div class="mt-3 rounded-xl bg-brand-50 p-4 dark:bg-brand-500/10">
                    <p class="text-theme-xs text-brand-600 dark:text-brand-400">{{ __('Jami obuna summasi') }}</p>
                    <p class="mt-1 text-lg font-bold text-brand-700 dark:text-brand-300">{{ number_format($subscriptionsAmount, 0, '.', ' ') }} {{ __('so‘m') }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
