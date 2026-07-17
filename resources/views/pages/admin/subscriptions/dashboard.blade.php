@extends('layouts.admin')

@section('title', __('Obunalar tahlili'))

@section('content')
    {{-- Title --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ __('Obunalar tahlili') }}</h2>
            <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">{{ __('Har bir nashr bo‘yicha yozilganlar soni va oylar bo‘yicha qamrovi') }}</p>
        </div>
        <a href="{{ route('admin.subscriptions.index') }}"
           class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">
            &larr; {{ __('Ro‘yxatga qaytish') }}
        </a>
    </div>

    {{-- Year filter --}}
    <form method="GET" action="{{ route('admin.subscriptions.dashboard') }}"
          class="mb-5 flex items-end gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="sm:w-32">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Yil') }}</label>
            <input type="number" name="year" value="{{ $year }}"
                   class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90" />
        </div>
        <button type="submit" class="bg-brand-500 hover:bg-brand-600 h-11 rounded-lg px-5 text-sm font-medium text-white transition">{{ __('Ko‘rsatish') }}</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="max-w-full overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-800">
                        <th class="px-3 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('№') }}</th>
                        <th class="px-3 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Indeks') }}</th>
                        <th class="px-3 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Nashr nomi') }}</th>
                        <th class="px-3 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Davriyligi') }}</th>
                        @for ($m = 1; $m <= 12; $m++)
                            <th class="px-2 py-3 text-center text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ $m }}</th>
                        @endfor
                        <th class="px-3 py-3 text-center text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Foizda') }}</th>
                        <th class="px-3 py-3 text-center text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Yozilganlar soni') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($coverage as $i => $row)
                        @php
                            $journal = $row['journal'];
                            $periodicity = $journal->periodicity && $journal->periodicity_count
                                ? "{$journal->periodicity_count} marta / {$journal->periodicity->label()}"
                                : '—';
                        @endphp
                        <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.02]">
                            <td class="px-3 py-3 text-theme-sm text-gray-500 dark:text-gray-400">{{ $i + 1 }}</td>
                            <td class="px-3 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $journal->index ?? '—' }}</td>
                            <td class="px-3 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $journal->name }}</td>
                            <td class="px-3 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $periodicity }}</td>
                            @for ($m = 1; $m <= 12; $m++)
                                <td class="px-2 py-3 text-center text-theme-sm {{ $row['months'][$m] > 0 ? 'font-medium text-gray-800 dark:text-white/90' : 'text-gray-300 dark:text-gray-700' }}">
                                    {{ $row['months'][$m] > 0 ? $row['months'][$m] : '—' }}
                                </td>
                            @endfor
                            <td class="px-3 py-3 text-center">
                                <span class="text-theme-xs inline-flex rounded-full px-2.5 py-0.5 font-medium {{ $row['percentage'] >= 90 ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500' : ($row['percentage'] >= 50 ? 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500' : 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500') }}">{{ $row['percentage'] }}%</span>
                            </td>
                            <td class="px-3 py-3 text-center text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $row['count'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="19" class="px-5 py-12 text-center text-theme-sm text-gray-500 dark:text-gray-400">{{ __(':year yil uchun obunalar topilmadi.', ['year' => $year]) }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
