@extends('layouts.admin')

@php
    $isNewspaper = ($filters['kind'] ?? null) === \App\Enums\PublicationKind::Newspaper->value;
    $pageTitle = $isNewspaper ? __('Gazeta maqolalari') : __('Maqolalar');
    $filtersWithoutKind = collect($filters)->except('kind')->all();
@endphp

@section('title', $pageTitle)

@section('content')
    {{-- Title + New article --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ $pageTitle }}</h2>
            <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">{{ __('Jami') }}: {{ $articles->total() }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.articles.create', array_filter(['kind' => $filters['kind'] ?? null])) }}"
               class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                <span class="text-lg leading-none">+</span> {{ $isNewspaper ? __('Yangi gazeta maqolasi') : __('Yangi maqola') }}
            </a>
        </div>
    </div>

    {{-- Success message --}}
    @if (session('success'))
        <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
    @endif

    {{-- Search / filter --}}
    <form method="GET" action="{{ route('admin.articles.index') }}"
          class="mb-5 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] sm:flex-row sm:items-end">
        <input type="hidden" name="kind" value="{{ $filters['kind'] ?? '' }}" />
        <div class="flex-1">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Qidirish') }}</label>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                   placeholder="{{ __('Sarlavha yoki muallif...') }}"
                   class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90" />
        </div>
        <div class="sm:w-48">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ $isNewspaper ? __('Gazeta') : __('Jurnal') }}</label>
            <select name="journal_id"
                    class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90">
                <option value="">{{ __('Barchasi') }}</option>
                @foreach ($journals as $journal)
                    <option value="{{ $journal->id }}" @selected(($filters['journal_id'] ?? null) == $journal->id)>{{ $journal->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:w-48">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Resurs sohasi') }}</label>
            <select name="resource_field_id"
                    class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90">
                <option value="">{{ __('Barchasi') }}</option>
                @foreach ($resourceFields as $field)
                    <option value="{{ $field->id }}" @selected(($filters['resource_field_id'] ?? null) == $field->id)>{{ $field->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 h-11 rounded-lg px-5 text-sm font-medium text-white transition">{{ __('Qidirish') }}</button>
            @if (array_filter($filtersWithoutKind))
                <a href="{{ route('admin.articles.index', array_filter(['kind' => $filters['kind'] ?? null])) }}" class="flex h-11 items-center rounded-lg border border-gray-200 px-4 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400">{{ __('Tozalash') }}</a>
            @endif
        </div>
    </form>

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="max-w-full overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-800">
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Maqola') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ $isNewspaper ? __('Gazeta / Son') : __('Jurnal / Son') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Resurs sohasi') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('PDF') }}</th>
                        <th class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Amallar') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($articles as $article)
                        <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.02]">
                            <td class="px-5 py-4">
                                <div class="min-w-0">
                                    <p class="text-theme-sm truncate font-medium text-gray-800 dark:text-white/90">{{ $article->title }}</p>
                                    <p class="text-theme-xs truncate text-gray-500 dark:text-gray-400">{{ $article->author }}</p>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">
                                @if ($article->journalIssue)
                                    {{ $article->journalIssue->journal?->name }}
                                    <span class="text-theme-xs text-gray-400">({{ $article->journalIssue->issue_number }}, {{ $article->journalIssue->year }})</span>
                                @elseif ($article->external_journal_name)
                                    {{ $article->external_journal_name }}
                                    <span class="text-theme-xs text-gray-400">{{ __('(tashqi)') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $article->resourceField?->name ?? '—' }}</td>
                            <td class="px-5 py-4 text-theme-xs">
                                <span class="{{ $article->electronic_file ? 'text-success-600' : 'text-gray-400' }}">{{ $article->electronic_file ? '📎' : '—' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.articles.show', $article) }}"
                                       class="text-theme-xs rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">{{ __('Ko‘rish') }}</a>
                                    <a href="{{ route('admin.articles.edit', $article) }}"
                                       class="text-theme-xs rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">{{ __('Tahrirlash') }}</a>
                                    @php $rowIsNewspaper = $article->journalIssue?->journal?->kind === \App\Enums\PublicationKind::Newspaper; @endphp
                                    <button type="button"
                                            @click="$store.confirm.ask('{{ route('admin.articles.destroy', $article) }}', '{{ $rowIsNewspaper ? __('Gazeta maqolasini o‘chirishni tasdiqlaysizmi?') : __('Maqolani o‘chirishni tasdiqlaysizmi?') }}')"
                                            class="text-theme-xs rounded-lg border border-red-200 px-3 py-1.5 font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center">
                                <x-admin.icon name="document-text" class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" />
                                <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">{{ $isNewspaper ? __('Gazeta maqolalari topilmadi.') : __('Maqolalar topilmadi.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-5">
        {{ $articles->links() }}
    </div>
@endsection
