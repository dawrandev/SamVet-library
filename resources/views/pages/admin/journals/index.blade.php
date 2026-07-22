@extends('layouts.admin')

@php
    $currentKindFilter = $filters['kind'] ?? null;
    $pageTitle = match ($currentKindFilter) {
        \App\Enums\PublicationKind::Newspaper->value => __('Gazetalar'),
        \App\Enums\PublicationKind::Journal->value => __('Jurnallar'),
        default => __('Davriy nashrlar'),
    };
    $filtersWithoutKind = collect($filters)->except('kind')->all();
@endphp

@section('title', $pageTitle)

@section('content')
    @include('partials.admin.periodicals-tabs', ['activeTab' => 'journals'])

    {{-- Title + New periodical --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ $pageTitle }}</h2>
            <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">{{ __('Jami') }}: {{ $journals->total() }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.journals.export', $filters) }}"
               class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.06]">
                <x-admin.icon name="download" class="h-4 w-4" /> {{ __('Excel export') }}
            </a>
            <a href="{{ route('admin.journals.create', array_filter(['kind' => $currentKindFilter])) }}"
               class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                <span class="text-lg leading-none">+</span> {{ __('Yangi davriy nashr') }}
            </a>
        </div>
    </div>

    {{-- Success message --}}
    @if (session('success'))
        <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
    @endif

    {{-- Search / filter --}}
    <form method="GET" action="{{ route('admin.journals.index') }}"
          class="mb-5 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] sm:flex-row sm:items-end">
        <div class="flex-1">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Qidirish') }}</label>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                   placeholder="{{ __('Nomi yoki ISSN...') }}"
                   class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90" />
        </div>
        <div class="sm:w-44">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Nashr turi') }}</label>
            <select name="kind"
                    class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90">
                <option value="">{{ __('Barchasi') }}</option>
                @foreach (\App\Enums\PublicationKind::cases() as $kind)
                    <option value="{{ $kind->value }}" @selected($currentKindFilter === $kind->value)>{{ $kind->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:w-48">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Turi') }}</label>
            <select name="journal_type_id"
                    class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90">
                <option value="">{{ __('Barchasi') }}</option>
                @foreach ($types as $type)
                    <option value="{{ $type->id }}" @selected(($filters['journal_type_id'] ?? null) == $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 h-11 rounded-lg px-5 text-sm font-medium text-white transition">{{ __('Qidirish') }}</button>
            @if (array_filter($filters))
                <a href="{{ route('admin.journals.index') }}" class="flex h-11 items-center rounded-lg border border-gray-200 px-4 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400">{{ __('Tozalash') }}</a>
            @endif
        </div>
    </form>

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="max-w-full overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-800">
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Jurnal') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Nashr turi') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Turi') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Tili') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Sonlar') }}</th>
                        <th class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Amallar') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($journals as $journal)
                        <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.02]">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-14 w-10 flex-shrink-0 items-center justify-center overflow-hidden rounded-md bg-gray-100 dark:bg-gray-800"><x-admin.icon name="newspaper" class="h-6 w-6 text-gray-400" /></div>
                                    <div class="min-w-0">
                                        <p class="text-theme-sm truncate font-medium text-gray-800 dark:text-white/90">{{ $journal->name }}</p>
                                        <p class="text-theme-xs truncate text-gray-500 dark:text-gray-400">{{ $journal->issn ? 'ISSN: ' . $journal->issn : '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-theme-xs inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">{{ $journal->kind?->label() ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $journal->type?->name ?? '—' }}</td>
                            <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $journal->language?->name ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <span class="text-theme-xs inline-flex rounded-full bg-brand-50 px-2.5 py-0.5 font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                    {{ $journal->issues_count }} {{ __('son') }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.journals.show', $journal) }}"
                                       class="text-theme-xs rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">{{ __('Ko‘rish') }}</a>
                                    <a href="{{ route('admin.journals.edit', $journal) }}"
                                       class="text-theme-xs rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">{{ __('Tahrirlash') }}</a>
                                    <button type="button"
                                            @click="$store.confirm.ask('{{ route('admin.journals.destroy', $journal) }}', '{{ $journal->kind === \App\Enums\PublicationKind::Newspaper ? __('Gazetani o‘chirishni tasdiqlaysizmi?') : __('Jurnalni o‘chirishni tasdiqlaysizmi?') }}')"
                                            class="text-theme-xs rounded-lg border border-red-200 px-3 py-1.5 font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <x-admin.icon name="newspaper" class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" />
                                <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">
                                    {{ match ($currentKindFilter) {
                                        \App\Enums\PublicationKind::Newspaper->value => __('Gazetalar topilmadi.'),
                                        \App\Enums\PublicationKind::Journal->value => __('Jurnallar topilmadi.'),
                                        default => __('Davriy nashrlar topilmadi.'),
                                    } }}
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Paginatsiya --}}
    <div class="mt-5">
        {{ $journals->links() }}
    </div>
@endsection
