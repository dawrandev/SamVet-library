@extends('layouts.site')

@php
    $heading = match ($activeKind) {
        \App\Enums\PublicationKind::Journal => __('Jurnallar'),
        \App\Enums\PublicationKind::Newspaper => __('Gazetalar'),
        default => __('Jurnal va gazetalar'),
    };

    $filters = [
        ['label' => __('Barchasi'), 'kind' => null],
        ['label' => __('Jurnallar'), 'kind' => \App\Enums\PublicationKind::Journal],
        ['label' => __('Gazetalar'), 'kind' => \App\Enums\PublicationKind::Newspaper],
    ];
@endphp

@section('title', $heading)

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <nav class="text-sm text-gray-500">
            <a href="{{ route('home') }}" class="hover:text-blue-700">{{ __('Bosh sahifa') }}</a>
            <span class="mx-1.5 text-gray-300">/</span>
            <a href="{{ route('sections') }}" class="hover:text-blue-700">{{ __('Bo‘limlar') }}</a>
            <span class="mx-1.5 text-gray-300">/</span>
            <span class="text-gray-700">{{ $heading }}</span>
        </nav>

        <div class="mt-3 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-gray-900">{{ $heading }}</h1>
                <p class="mt-1.5 text-sm text-gray-500">{{ __('Davriy nashrlar — sonlarini va maqolalarini ko‘ring.') }}</p>
            </div>
            <span class="rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700">
                {{ __(':n ta nashr', ['n' => number_format($periodicals->total(), 0, '.', ' ')]) }}
            </span>
        </div>

        {{-- Kind filter --}}
        <div class="mt-6 flex flex-wrap gap-2">
            @foreach ($filters as $filter)
                @php $active = $activeKind === $filter['kind']; @endphp
                <a href="{{ route('periodicals.index', $filter['kind'] ? ['kind' => $filter['kind']->value] : []) }}"
                   @class([
                       'rounded-full px-4 py-2 text-sm font-medium transition',
                       'bg-blue-700 text-white' => $active,
                       'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50' => ! $active,
                   ])>{{ $filter['label'] }}</a>
            @endforeach
        </div>

        @if ($periodicals->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center">
                <p class="text-sm font-semibold text-gray-900">{{ __('Hozircha nashrlar yo‘q') }}</p>
                <p class="mt-1 text-sm text-gray-500">{{ __('Davriy nashrlar qo‘shilgach shu yerda ko‘rinadi.') }}</p>
            </div>
        @else
            <div class="mt-7 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($periodicals as $periodical)
                    @php
                        // Newspapers use the fixed NewspaperType enum; journals keep the journal_type_id lookup.
                        $periodicalType = $periodical->kind === \App\Enums\PublicationKind::Newspaper
                            ? $periodical->newspaper_type?->label()
                            : $periodical->type?->name;
                    @endphp
                    <a href="{{ route('journal.show', $periodical->slug) }}"
                       class="group flex gap-4 rounded-2xl border border-gray-200 bg-white p-4 transition hover:-translate-y-0.5 hover:shadow-lg">
                        <div class="relative h-24 w-20 flex-none overflow-hidden rounded-lg border-t-2 border-blue-600 bg-blue-50">
                            <div class="absolute inset-0 opacity-40" style="background-image: repeating-linear-gradient(135deg, #ffffff 0 10px, #dbeafe 10px 20px);"></div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="text-[11px] font-semibold text-blue-700">{{ $periodical->kind->label() }}</span>
                            <h2 class="mt-0.5 line-clamp-2 font-bold text-gray-900 group-hover:text-blue-700">{{ $periodical->name }}</h2>
                            <p class="mt-1 line-clamp-1 text-xs text-gray-500">
                                {{ $periodicalType }}@if ($periodical->periodicity_unit) · {{ $periodical->periodicityLabel() }}@endif
                            </p>
                            <p class="mt-2 text-xs font-medium text-gray-400">{{ __(':n ta son', ['n' => $periodical->issues_count]) }}</p>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-10">
                {{ $periodicals->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
@endsection
