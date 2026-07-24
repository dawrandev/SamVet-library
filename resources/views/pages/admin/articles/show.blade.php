@extends('layouts.admin')

@section('title', $article->title)

@section('content')
    @php
        $journal = $article->journalIssue?->journal;
        $isNewspaper = $journal?->kind === \App\Enums\PublicationKind::Newspaper;
        // A library-external article has no journal → always lives under "Maqolalar".
        $backParams = array_filter(['kind' => $journal?->kind?->value ?? \App\Enums\PublicationKind::Journal->value]);

        // Article's own fields
        $details = array_filter([
            __('Muallif(lar)') => $article->author,
            __('Resurs sohasi') => $article->resourceField?->name,
            __('Tili') => $article->language?->name,
            __('Kategoriyasi') => $article->category?->label(),
            __('DOI') => $article->doi,
            __('Sahifalar') => $article->pages,
        ], fn ($v) => filled($v));

        // Inherited meta (from the parent issue → journal — displayed, not stored).
        // A library-external article has no journal — its own free-text name/year instead.
        $inherited = $article->isExternal()
            ? array_filter([
                __('Jurnal nomi') => $article->external_journal_name,
                __('Nashr yili') => $article->external_journal_year,
            ], fn ($v) => filled($v))
            : array_filter([
                ($isNewspaper ? __('Gazeta nomi') : __('Jurnal nomi')) => $journal?->name,
                ($isNewspaper ? __('Gazeta turi') : __('Jurnal turi')) => $journal?->type?->name,
                __('Nashriyoti') => $journal?->publisher,
                __('Nashr joyi') => $journal?->publicationPlace?->name,
                __('Yili') => $article->journalIssue?->year,
                ($isNewspaper ? __('Gazeta soni') : __('Soni')) => $article->journalIssue?->issue_number,
                __('Chiqqan sanasi') => $article->journalIssue?->issue_date?->format('d.m.Y'),
            ], fn ($v) => filled($v));

        $journalNameLabel = $isNewspaper ? __('Gazeta nomi') : __('Jurnal nomi');
    @endphp

    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.articles.index', $backParams) }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ $article->title }}</h2>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.articles.edit', $article) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Tahrirlash') }}</a>
            <button type="button"
                    @click="$store.confirm.ask('{{ route('admin.articles.destroy', $article) }}', '{{ $isNewspaper ? __('Gazeta maqolasini o‘chirishni tasdiqlaysizmi?') : __('Maqolani o‘chirishni tasdiqlaysizmi?') }}')"
                    class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
        </div>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
    @endif

    <div class="grid grid-cols-12 gap-6">
        {{-- Left: article details --}}
        <div class="col-span-12 space-y-6 xl:col-span-7">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Maqola haqida ma’lumot') }}</h3>
                <dl class="space-y-3">
                    @foreach ($details as $label => $value)
                        <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-800/50">
                            <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                            <dd class="text-theme-sm text-right font-medium text-gray-800 dark:text-white/90">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($article->annotation)
                    <div class="mt-5">
                        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Annotatsiya') }}</h4>
                        <p class="text-theme-sm whitespace-pre-line text-gray-600 dark:text-gray-400">{{ $article->annotation }}</p>
                    </div>
                @endif
            </div>

            {{-- Electronic file indicator --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Elektron fayl') }}</h3>
                @if ($article->electronic_file)
                    <p class="text-theme-sm inline-flex items-center gap-2 text-success-600">
                        <span>📎</span> {{ __('PDF fayl biriktirilgan (himoyalangan).') }}
                    </p>
                @else
                    <p class="text-theme-sm text-gray-400">{{ __('Elektron fayl biriktirilmagan.') }}</p>
                @endif
            </div>
        </div>

        {{-- Right: inherited journal meta + location --}}
        <div class="col-span-12 space-y-6 xl:col-span-5">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">
                    @if ($article->isExternal())
                        {{ __('Tashqi jurnal haqida ma’lumot') }}
                    @else
                        {{ $isNewspaper ? __('Gazeta haqida ma’lumot') : __('Jurnal haqida ma’lumot') }}
                    @endif
                </h3>
                <dl class="space-y-3">
                    @forelse ($inherited as $label => $value)
                        <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-800/50">
                            <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                            <dd class="text-theme-sm text-right font-medium text-gray-800 dark:text-white/90">
                                @if ($label === $journalNameLabel && $journal)
                                    <a href="{{ route('admin.journals.show', $journal) }}" class="text-brand-600 hover:underline dark:text-brand-400">{{ $value }}</a>
                                @else
                                    {{ $value }}
                                @endif
                            </dd>
                        </div>
                    @empty
                        <p class="text-theme-sm text-gray-400">{{ __('Ma’lumot yo‘q') }}</p>
                    @endforelse
                </dl>
            </div>
        </div>
    </div>
@endsection
