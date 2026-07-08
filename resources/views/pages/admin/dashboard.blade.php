@extends('layouts.admin')

@section('title', __('Bosh sahifa'))

@section('content')
    @php
        // color-name -> progress-bar class
        $barClass = [
            'success' => 'bg-success-500',
            'brand' => 'bg-brand-500',
            'error' => 'bg-error-500',
            'warning' => 'bg-warning-500',
            'gray' => 'bg-gray-300 dark:bg-gray-600',
        ];
        $copyColor = ['available' => 'success', 'borrowed' => 'brand', 'lost' => 'error', 'written_off' => 'gray'];
        $today = now()->startOfDay();
    @endphp

    {{-- ===== Row 1: KPI cards ===== --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 md:gap-6">
        {{-- Books --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                <x-admin.icon name="book" class="h-6 w-6" />
            </div>
            <div class="mt-5">
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('Kitob nomlari') }}</span>
                <h4 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90">{{ number_format($booksTotal, 0, '.', ' ') }}</h4>
                <p class="text-theme-xs mt-1 text-gray-400">{{ number_format($copiesTotal, 0, '.', ' ') }} {{ __('nusxa') }}</p>
            </div>
        </div>

        {{-- Readers --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                <x-admin.icon name="users" class="h-6 w-6" />
            </div>
            <div class="mt-5">
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('Foydalanuvchilar') }}</span>
                <h4 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90">{{ number_format($readersTotal, 0, '.', ' ') }}</h4>
                <p class="text-theme-xs mt-1 text-success-600 dark:text-success-500">{{ number_format($readersActive, 0, '.', ' ') }} {{ __('faol') }}</p>
            </div>
        </div>

        {{-- Active loans --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                <x-admin.icon name="book" class="h-6 w-6" />
            </div>
            <div class="mt-5">
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('Hozir berilgan') }}</span>
                <h4 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90">{{ number_format($loansActive, 0, '.', ' ') }}</h4>
                <p class="text-theme-xs mt-1 text-gray-400">{{ number_format($copiesAvailable, 0, '.', ' ') }} {{ __('nusxa mavjud') }}</p>
            </div>
        </div>

        {{-- Overdue (clickable) --}}
        <a href="{{ route('admin.loans.index', ['scope' => 'overdue']) }}"
           class="block rounded-2xl border border-gray-200 bg-white p-5 transition hover:border-error-300 dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-error-500/40 md:p-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500">
                <x-admin.icon name="clock" class="h-6 w-6" />
            </div>
            <div class="mt-5">
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('Muddati o‘tgan') }}</span>
                <h4 class="mt-1 text-2xl font-bold {{ $overdue > 0 ? 'text-error-600 dark:text-error-500' : 'text-gray-800 dark:text-white/90' }}">{{ number_format($overdue, 0, '.', ' ') }}</h4>
                <p class="text-theme-xs mt-1 text-gray-400">{{ __('ko‘rish uchun bosing') }}</p>
            </div>
        </a>
    </div>

    {{-- ===== Row 2: breakdowns ===== --}}
    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3 md:gap-6">
        {{-- Copy status --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Fond holati') }}</h3>
            <p class="text-theme-xs mt-0.5 text-gray-400">{{ __('Nusxalar holat bo‘yicha') }}</p>
            <div class="mt-5 space-y-4">
                @foreach (\App\Enums\CopyStatus::cases() as $st)
                    @php $c = (int) ($copiesByStatus[$st->value] ?? 0); $pct = $copiesTotal > 0 ? round($c / $copiesTotal * 100) : 0; @endphp
                    <div>
                        <div class="text-theme-sm flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ $st->label() }}</span>
                            <span class="font-semibold text-gray-800 dark:text-white/90">{{ $c }}</span>
                        </div>
                        <div class="mt-1.5 h-2 w-full rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-2 rounded-full {{ $barClass[$copyColor[$st->value] ?? 'gray'] }}" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Readers by type --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Foydalanuvchilar turi') }}</h3>
            <p class="text-theme-xs mt-0.5 text-gray-400">{{ __('Tur bo‘yicha taqsimot') }}</p>
            <div class="mt-5 space-y-3">
                @foreach (\App\Enums\ReaderType::cases() as $t)
                    @php $c = (int) ($readersByType[$t->value] ?? 0); $pct = $readersTotal > 0 ? round($c / $readersTotal * 100) : 0; @endphp
                    <div>
                        <div class="text-theme-sm flex items-center justify-between">
                            <span class="truncate text-gray-600 dark:text-gray-400">{{ $t->label() }}</span>
                            <span class="ml-2 font-semibold text-gray-800 dark:text-white/90">{{ $c }}</span>
                        </div>
                        <div class="mt-1 h-1.5 w-full rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-1.5 rounded-full bg-brand-500" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Computers by status --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Kompyuterlar holati') }}</h3>
            <p class="text-theme-xs mt-0.5 text-gray-400">{{ __('Elektron o‘qish zali') }}</p>
            <div class="mt-5 space-y-4">
                @foreach (\App\Enums\ComputerStatus::cases() as $st)
                    @php $c = (int) ($computersByStatus[$st->value] ?? 0); $pct = $computersTotal > 0 ? round($c / $computersTotal * 100) : 0; @endphp
                    <div>
                        <div class="text-theme-sm flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ $st->label() }}</span>
                            <span class="font-semibold text-gray-800 dark:text-white/90">{{ $c }}</span>
                        </div>
                        <div class="mt-1.5 h-2 w-full rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-2 rounded-full {{ $barClass[$st->color()] ?? $barClass['gray'] }}" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
                @if ($computersTotal === 0)
                    <p class="text-theme-sm text-gray-400">{{ __('Hali kompyuter kiritilmagan.') }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- ===== Row 3: recent loans + quick counts ===== --}}
    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3 md:gap-6">
        {{-- Recent loans --}}
        <div class="lg:col-span-2 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between px-5 py-4 sm:px-6">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('So‘nggi berilgan kitoblar') }}</h3>
                <a href="{{ route('admin.loans.index') }}" class="text-theme-sm font-medium text-brand-500 hover:text-brand-600">{{ __('Barchasi') }}</a>
            </div>
            <div class="max-w-full overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-y border-gray-100 dark:border-gray-800">
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400 sm:px-6">{{ __('Foydalanuvchi') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Kitob') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Berilgan') }}</th>
                            <th class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400 sm:px-6">{{ __('Muddat') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentLoans as $loan)
                            @php $isOverdue = $loan->status === \App\Enums\LoanStatus::OnLoan && $loan->due_at && $loan->due_at->lt($today); @endphp
                            <tr class="border-b border-gray-50 last:border-0 dark:border-gray-800/60">
                                <td class="px-5 py-3.5 text-theme-sm font-medium text-gray-800 dark:text-white/90 sm:px-6">{{ $loan->reader?->full_name ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-theme-sm text-gray-600 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($loan->copy?->book?->title ?? '—', 32) }}</td>
                                <td class="px-5 py-3.5 text-theme-sm text-gray-600 dark:text-gray-400">{{ $loan->issued_at?->format('d.m.Y') ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-right sm:px-6">
                                    <span class="text-theme-xs inline-flex rounded-full px-2.5 py-0.5 font-medium {{ $isOverdue ? 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">
                                        {{ $loan->due_at?->format('d.m.Y') ?? '—' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center">
                                    <x-admin.icon name="book" class="mx-auto h-9 w-9 text-gray-300 dark:text-gray-600" />
                                    <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Hali kitob berilmagan.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Quick counts --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Qisqa hisobot') }}</h3>
            <div class="mt-4 space-y-1">
                @php
                    $counts = [
                        ['newspaper', __('Jurnallar'), $journalsTotal],
                        ['document-text', __('Maqolalar'), $articlesTotal],
                        ['newspaper', __('Yangiliklar'), $newsTotal],
                        ['computer-desktop', __('Kompyuterlar'), $computersTotal],
                        ['clipboard-list', __('Obunalar'), $subscriptionsTotal],
                        ['folder', __('Kategoriyalar'), $categoriesTotal],
                        ['users', __('Mualliflar'), $authorsTotal],
                    ];
                @endphp
                @foreach ($counts as [$icon, $label, $value])
                    <div class="flex items-center justify-between rounded-lg px-2 py-2.5 hover:bg-gray-50 dark:hover:bg-white/5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                <x-admin.icon :name="$icon" class="h-4 w-4" />
                            </span>
                            <span class="text-theme-sm text-gray-600 dark:text-gray-400">{{ $label }}</span>
                        </div>
                        <span class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">{{ number_format($value, 0, '.', ' ') }}</span>
                    </div>
                @endforeach
            </div>

            <div class="mt-3 rounded-xl bg-brand-50 p-4 dark:bg-brand-500/10">
                <p class="text-theme-xs text-brand-600 dark:text-brand-400">{{ __('Jami obuna summasi') }}</p>
                <p class="mt-1 text-lg font-bold text-brand-700 dark:text-brand-300">{{ number_format($subscriptionsAmount, 0, '.', ' ') }} {{ __('so‘m') }}</p>
            </div>
        </div>
    </div>
@endsection
