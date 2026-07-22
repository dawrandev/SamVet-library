@extends('layouts.site')

@section('title', $journal->name)

@php
    $kind = $journal->kind;   // PublicationKind (journal / newspaper)
    $isNewspaper = $kind === \App\Enums\PublicationKind::Newspaper;

    // Muassis/Nashr joyi/Indeks are library-internal (kutubxona ichki
    // ma'lumoti) — shown only in the admin panel, not on the public site.
    $periodicity = $journal->periodicityLabel();

    // Newspapers use the fixed NewspaperType enum; journals keep the journal_type_id lookup.
    $type = $isNewspaper ? $journal->newspaper_type?->label() : $journal->type?->name;

    $rows = array_filter([
        [$kind->label().' '.__('nomi'), $journal->name],
        [__('Turi'), $type],
        [__('Davriyligi'), $periodicity],
        ['ISSN', $journal->issn],
        [__('Tili'), $journal->language?->name],
        [__('Yili'), $sinceYear ? __(':y yildan', ['y' => $sinceYear]) : null],
    ], fn ($row) => filled($row[1]));
@endphp

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <nav class="flex flex-wrap items-center gap-1.5 text-sm text-gray-500">
            <a href="{{ route('home') }}" class="hover:text-blue-700">{{ __('Bosh sahifa') }}</a>
            <span class="text-gray-300">/</span>
            <span class="text-gray-500">{{ $kind->label() === __('Gazeta') ? __('Gazetalar') : __('Jurnallar') }}</span>
            <span class="text-gray-300">/</span>
            <span class="line-clamp-1 text-gray-700">{{ $journal->name }}</span>
        </nav>

        <div class="mt-5 grid gap-8 lg:grid-cols-[300px_minmax(0,1fr)]">
            {{-- Left: cover + summary --}}
            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
                <div class="relative flex h-80 items-end justify-center overflow-hidden rounded-2xl border-t-2 border-blue-600 bg-blue-50">
                    <div class="absolute inset-0 opacity-40" style="background-image: repeating-linear-gradient(135deg, #ffffff 0 12px, #dbeafe 12px 24px);"></div>
                    <span class="absolute left-3 top-3 inline-flex items-center gap-1.5 rounded-md bg-white/90 px-2.5 py-1 text-xs font-semibold text-blue-700">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292" /></svg>
                        {{ $kind->label() }}
                    </span>
                    <span class="relative mb-3 text-[10px] uppercase tracking-wide text-blue-300">{{ __('muqova') }}</span>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-4 text-center text-sm font-medium {{ $issues->isNotEmpty() ? 'text-green-700' : 'text-gray-500' }}">
                    @if ($issues->isNotEmpty())
                        <span class="mr-1.5 inline-block h-1.5 w-1.5 rounded-full bg-green-500"></span>
                        {{ __(':n ta son mavjud', ['n' => $issues->count()]) }}
                    @else
                        {{ __('Hozircha sonlar yo‘q') }}
                    @endif
                </div>

                <div class="flex gap-3 rounded-2xl border border-blue-100 bg-blue-50/50 p-4">
                    <svg class="h-5 w-5 flex-none text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                    <p class="text-xs leading-relaxed text-gray-600">{{ __('Sonlar Axborot resurs markazi o‘qish zalida online tarzda o‘qiladi.') }}</p>
                </div>
            </aside>

            {{-- Right: masthead info --}}
            <div>
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    @if ($type)
                        <span class="font-semibold text-blue-700">{{ $type }}</span>
                    @endif
                    <span class="text-gray-500">
                        @if ($periodicity){{ $periodicity }}@endif
                        @if ($sinceYear) · {{ __(':y yildan', ['y' => $sinceYear]) }}@endif
                    </span>
                </div>

                <h1 class="mt-2 text-3xl font-extrabold leading-tight tracking-tight text-gray-900">{{ $journal->name }}</h1>

                <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white">
                    <h2 class="border-b border-gray-100 px-5 py-3.5 text-sm font-bold text-gray-900">{{ __('Nashr ma‘lumotlari') }}</h2>
                    <dl class="divide-y divide-gray-100">
                        @foreach ($rows as [$label, $value])
                            <div class="grid grid-cols-1 gap-1 px-5 py-3 sm:grid-cols-[200px_1fr] sm:gap-4">
                                <dt class="text-sm text-gray-500">{{ $label }}</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        </div>

        {{-- ===== Issues ===== --}}
        @if ($issues->isNotEmpty())
            <section class="mt-14">
                <h2 class="text-2xl font-bold tracking-tight text-gray-900">{{ __('Sonlar') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Sonni tanlang — maqolalar ro‘yxati quyida ochiladi.') }}</p>

                <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($issues as $issue)
                        @php $isActive = $selected && $issue->id === $selected->id; @endphp
                        <a href="{{ route('journal.show', $journal->slug) }}?son={{ $issue->id }}#maqolalar"
                           @class([
                               'flex items-center gap-4 rounded-2xl border bg-white p-4 transition hover:shadow-md',
                               'border-blue-500 ring-1 ring-blue-500' => $isActive,
                               'border-gray-200' => ! $isActive,
                           ])>
                            <div class="relative h-16 w-14 flex-none overflow-hidden rounded-md border-t-2 border-blue-500 bg-blue-50">
                                <div class="absolute inset-0 opacity-40" style="background-image: repeating-linear-gradient(135deg, #ffffff 0 8px, #dbeafe 8px 16px);"></div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-gray-900">{{ $issue->year }} · №{{ $issue->issue_number }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ __(':n bet', ['n' => $issue->pages]) }}</p>
                                <p class="mt-0.5 text-xs font-medium text-blue-700">{{ __(':n maqola', ['n' => $issue->articles_count]) }}</p>
                            </div>
                            @if ($isActive)
                                <span class="flex h-6 w-6 flex-none items-center justify-center rounded-full bg-blue-600 text-white">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>

            {{-- ===== Articles of the selected issue ===== --}}
            <section id="maqolalar" class="mt-8 scroll-mt-24 overflow-hidden rounded-2xl border border-gray-200 bg-white">
                <div class="flex items-center justify-between gap-4 border-b border-gray-100 px-5 py-4">
                    <h2 class="text-base font-bold text-gray-900">{{ __('Maqolalar') }} — {{ $selected->year }}, №{{ $selected->issue_number }}</h2>
                    <span class="flex-none text-sm text-gray-400">{{ __(':n ta maqola', ['n' => $articles->count()]) }}</span>
                </div>

                @if ($articles->isEmpty())
                    <p class="px-5 py-8 text-center text-sm text-gray-500">{{ __('Bu sonда maqolalar yo‘q.') }}</p>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach ($articles as $article)
                            <x-site.article-row :article="$article" :number="$loop->iteration" />
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    </div>
@endsection
