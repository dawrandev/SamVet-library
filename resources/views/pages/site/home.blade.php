@extends('layouts.site')

@section('title', __('Bosh sahifa'))

@section('content')
    {{-- ===== Hero (asymmetric: text + search on the left, gateway panel on the right) ===== --}}
    <section class="bg-blue-900 text-white">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-12 lg:items-center lg:gap-8 lg:py-20 lg:px-8">
            <div class="lg:col-span-7">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-blue-100">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                    {{ __('Axborot resurs markazi (ARM)') }}
                </span>

                <h1 class="mt-5 text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl">
                    {{ __('Universitet fondining raqamli darvozasi') }}
                </h1>
                <p class="mt-4 max-w-xl text-base leading-relaxed text-blue-100/80">
                    {{ __('Samarqand davlat veterinariya meditsinasi, chorvachilik va biotexnologiyalar universiteti Nukus filiali — o‘qing va izlang.') }}
                </p>

                {{-- Search --}}
                <form action="{{ route('catalog') }}" method="GET" class="mt-8 flex max-w-2xl overflow-hidden rounded-xl bg-white p-1.5 shadow-lg">
                    <div class="flex flex-1 items-center gap-2 pl-3">
                        <svg class="h-5 w-5 flex-none text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.34-4.34M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" /></svg>
                        <input type="text" name="q" placeholder="{{ __('Kitob, muallif, ISBN yoki kalit so‘z...') }}"
                               class="w-full bg-transparent py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:outline-none" />
                    </div>
                    <button type="submit" class="flex items-center gap-2 rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.34-4.34M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" /></svg>
                        {{ __('Qidirish') }}
                    </button>
                </form>

                {{-- Quick type chips --}}
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ([__('Barchasi'), __('Kitob nomi'), __('Muallif'), 'ISBN', __('Mavzu')] as $i => $chip)
                        <button type="button"
                                @class([
                                    'rounded-full px-3.5 py-1.5 text-xs font-medium transition',
                                    'bg-white text-blue-900' => $i === 0,
                                    'bg-white/10 text-blue-100 hover:bg-white/20' => $i !== 0,
                                ])>{{ $chip }}</button>
                    @endforeach
                </div>
            </div>

            {{-- Gateway panel --}}
            <div class="lg:col-span-5">
                <div class="rounded-2xl bg-white/10 p-6 ring-1 ring-white/15 backdrop-blur">
                    <p class="text-sm font-medium text-blue-100">{{ __('Fond bir qarashda') }}</p>
                    <div class="mt-5 grid grid-cols-2 gap-4">
                        <div class="rounded-xl bg-white/10 p-4">
                            <p class="text-2xl font-bold">{{ number_format($stats['copies'], 0, '.', ' ') }}</p>
                            <p class="mt-1 text-xs text-blue-100/70">{{ __('Jami kitoblar') }}</p>
                        </div>
                        <div class="rounded-xl bg-white/10 p-4">
                            <p class="text-2xl font-bold">{{ number_format($stats['titles'], 0, '.', ' ') }}</p>
                            <p class="mt-1 text-xs text-blue-100/70">{{ __('Kitob nomlari') }}</p>
                        </div>
                        <div class="rounded-xl bg-white/10 p-4">
                            <p class="text-2xl font-bold">{{ number_format($stats['periodicals'], 0, '.', ' ') }}</p>
                            <p class="mt-1 text-xs text-blue-100/70">{{ __('Jurnal / gazetalar') }}</p>
                        </div>
                        <div class="rounded-xl bg-white/10 p-4">
                            <p class="text-2xl font-bold">{{ number_format($stats['articles'], 0, '.', ' ') }}</p>
                            <p class="mt-1 text-xs text-blue-100/70">{{ __('Maqolalar') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== Statistics band ===== --}}
    <section class="mx-auto -mt-8 max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 divide-gray-100 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm sm:grid-cols-3 lg:grid-cols-5 lg:divide-x">
            @php
                $band = [
                    ['icon' => 'book', 'value' => $stats['copies'], 'label' => __('Jami kitoblar')],
                    ['icon' => 'book', 'value' => $stats['titles'], 'label' => __('Kitob nomlari')],
                    ['icon' => 'users', 'value' => $stats['readers'], 'label' => __('Foydalanuvchilar')],
                    ['icon' => 'news', 'value' => $stats['periodicals'], 'label' => __('Jurnal / gazetalar')],
                    ['icon' => 'doc', 'value' => $stats['articles'], 'label' => __('Maqolalar')],
                ];
            @endphp
            @foreach ($band as $s)
                <div class="flex items-center gap-3 border-b border-gray-100 p-5 last:border-b-0 sm:border-b-0">
                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" /></svg>
                    </span>
                    <div>
                        <p class="text-xl font-bold text-gray-900">{{ number_format($s['value'], 0, '.', ' ') }}</p>
                        <p class="text-xs text-gray-500">{{ $s['label'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ===== Collection tiles ===== --}}
    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <p class="text-xs font-semibold uppercase tracking-wider text-blue-700">{{ __('Fond tarkibi') }}</p>
        <div class="mt-1 flex items-end justify-between">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('Elektron kutubxona bo‘limlari') }}</h2>
            <a href="#" class="text-sm font-medium text-blue-700 hover:text-blue-800">{{ __('Barcha bo‘limlar') }} →</a>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($collectionTiles as $tile)
                <a href="#" class="group rounded-2xl border border-gray-200 bg-white p-5 transition hover:border-blue-300 hover:shadow-md">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" /></svg>
                    </span>
                    <h3 class="mt-4 font-semibold text-gray-900 group-hover:text-blue-700">{{ $tile['label'] }}</h3>
                    <p class="mt-0.5 text-xs text-gray-400">{{ number_format($tile['count'], 0, '.', ' ') }} {{ __('ta manba') }}</p>
                </a>
            @endforeach

            <a href="#" class="flex flex-col justify-between rounded-2xl bg-blue-700 p-5 text-white transition hover:bg-blue-800">
                <h3 class="font-semibold">{{ __('Barcha resurslar katalogi') }}</h3>
                <span class="mt-6 text-sm font-medium text-blue-100">{{ __('Katalogga o‘tish') }} →</span>
            </a>
        </div>
    </section>

    {{-- ===== Most read ===== --}}
    @if ($mostRead->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pb-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-end justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ __('Eng ko‘p o‘qilgan') }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Foydalanuvchilar orasida eng talabgir manbalar') }}</p>
                </div>
                <a href="#" class="text-sm font-medium text-blue-700 hover:text-blue-800">{{ __('Barchasi') }} →</a>
            </div>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ($mostRead as $book)
                    <x-site.book-card :book="$book" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- ===== New arrivals ===== --}}
    @if ($newArrivals->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-end justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ __('Yangi kelgan kitoblar') }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Fondga so‘nggi qo‘shilgan nashrlar') }}</p>
                </div>
                <a href="#" class="text-sm font-medium text-blue-700 hover:text-blue-800">{{ __('Barchasi') }} →</a>
            </div>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ($newArrivals as $book)
                    <x-site.book-card :book="$book" :badge="__('Yangi')" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- ===== Latest news ===== --}}
    @if ($latestNews->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-end justify-between">
                <h2 class="text-2xl font-bold text-gray-900">{{ __('So‘nggi yangiliklar') }}</h2>
                <a href="#" class="text-sm font-medium text-blue-700 hover:text-blue-800">{{ __('Barcha yangiliklar') }} →</a>
            </div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($latestNews as $item)
                    <a href="#" class="group flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white transition hover:shadow-md">
                        <div class="relative h-40 overflow-hidden bg-blue-50">
                            @if ($item->cover_image)
                                <img src="{{ asset('storage/' . $item->cover_image) }}" alt="" class="h-full w-full object-cover" />
                            @else
                                <div class="absolute inset-0 opacity-40" style="background-image: repeating-linear-gradient(135deg, #ffffff 0 10px, #dbeafe 10px 20px);"></div>
                            @endif
                            @if ($item->category)
                                <span class="absolute left-3 top-3 rounded-md bg-blue-700 px-2 py-0.5 text-[11px] font-semibold text-white">{{ $item->category->name }}</span>
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col p-4">
                            <h3 class="line-clamp-2 font-semibold text-gray-900 group-hover:text-blue-700">{{ $item->getTranslation('title', 'uz') }}</h3>
                            @if ($item->getTranslation('excerpt', 'uz'))
                                <p class="mt-1.5 line-clamp-2 text-sm text-gray-500">{{ $item->getTranslation('excerpt', 'uz') }}</p>
                            @endif
                            <div class="mt-auto flex items-center justify-between pt-4 text-xs">
                                <span class="text-gray-400">{{ $item->published_at?->format('d.m.Y') }}</span>
                                <span class="font-medium text-blue-700">{{ __('Davomini o‘qish') }} →</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
@endsection
