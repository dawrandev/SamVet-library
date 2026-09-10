@extends('layouts.site')

@section('title', __('Maqolalar'))

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <nav class="text-sm text-gray-500">
            <a href="{{ route('home') }}" class="hover:text-blue-700">{{ __('Bosh sahifa') }}</a>
            <span class="mx-1.5 text-gray-300">/</span>
            <a href="{{ route('sections') }}" class="hover:text-blue-700">{{ __('Bo‘limlar') }}</a>
            <span class="mx-1.5 text-gray-300">/</span>
            <span class="text-gray-700">{{ __('Maqolalar') }}</span>
        </nav>

        <div class="mt-3 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-gray-900">{{ __('Maqolalar') }}</h1>
                <p class="mt-1.5 text-sm text-gray-500">{{ __('To‘liq matnni o‘qish uchun tizimga kirish talab qilinadi.') }}</p>
            </div>
            <span class="rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700">
                {{ __(':n ta maqola', ['n' => number_format($articles->total(), 0, '.', ' ')]) }}
            </span>
        </div>

        {{-- Search --}}
        <form method="GET" action="{{ route('articles.index') }}" class="mt-6 flex max-w-md gap-2">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                   placeholder="{{ __('Nomi yoki muallifi bo‘yicha qidirish...') }}"
                   class="h-11 w-full rounded-lg border border-gray-200 px-4 text-sm text-gray-800 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
            <button type="submit" class="h-11 flex-none rounded-lg bg-blue-700 px-5 text-sm font-medium text-white transition hover:bg-blue-800">{{ __('Qidirish') }}</button>
        </form>

        @if ($articles->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center">
                <p class="text-sm font-semibold text-gray-900">{{ __('Hozircha maqolalar yo‘q') }}</p>
                <p class="mt-1 text-sm text-gray-500">{{ __('Maqolalar qo‘shilgach shu yerda ko‘rinadi.') }}</p>
            </div>
        @else
            <div class="mt-7 divide-y divide-gray-100 overflow-hidden rounded-2xl border border-gray-200 bg-white">
                @foreach ($articles as $article)
                    {{--
                        Row markup is inline rather than <x-site.article-row>: that
                        component prints the "Online o‘qish" pill unconditionally,
                        which would promise a full text that a bibliographic-only
                        article does not have.
                    --}}
                    <a href="{{ route('article.show', $article->slug) }}"
                       class="flex items-center gap-4 px-5 py-4 transition hover:bg-gray-50">
                        <div class="min-w-0 flex-1">
                            <p class="line-clamp-2 text-sm font-semibold text-gray-900">{{ $article->title }}</p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ $article->author ?: '—' }}
                                {{-- A library-held article names its journal; an external one carries the title as free text. --}}
                                @php($journalName = $article->journalIssue?->journal?->name ?: $article->external_journal_name)
                                @if ($journalName) · {{ $journalName }} @endif
                                @if ($article->pages) · {{ $article->pages }}-{{ __('bet') }} @endif
                            </p>
                        </div>
                        @if (filled($article->electronic_file))
                            <span class="inline-flex flex-none items-center gap-1.5 rounded-lg border border-blue-200 px-3 py-1.5 text-xs font-semibold text-blue-700">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                {{ __('Online o‘qish') }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>

            <div class="mt-10">
                {{ $articles->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
@endsection
