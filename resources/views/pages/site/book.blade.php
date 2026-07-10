@extends('layouts.site')

@section('title', $book->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) $book->annotation), 160))

@php
    $authors = $book->authors->pluck('name')->join(', ');
    $available = (int) ($book->available_copies ?? 0);

    // Bibliographic rows — empty values are dropped so the table only shows real data.
    $rows = array_filter([
        ['UO‘K', $book->udc],
        [__('Avtorlik belgisi'), $book->author_mark],
        [__('Sarlavhasi'), $book->title],
        [__('Muallifi'), $authors],
        [__('Turi'), $book->type?->name],
        [__('Nashr joyi'), $book->publication_place],
        ['ISBN', $book->isbn],
        [__('Tili'), $book->language?->name],
        [__('Nashriyoti'), $book->publisher?->name],
        [__('Beti'), $book->pages ? __(':n b.', ['n' => $book->pages]) : null],
        [__('Yili'), $book->publication_year],
    ], fn ($row) => filled($row[1]));
@endphp

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <nav class="flex flex-wrap items-center gap-1.5 text-sm text-gray-500">
            <a href="{{ route('home') }}" class="hover:text-blue-700">{{ __('Bosh sahifa') }}</a>
            <span class="text-gray-300">/</span>
            <a href="{{ route('catalog') }}" class="hover:text-blue-700">{{ __('Elektron katalog') }}</a>
            <span class="text-gray-300">/</span>
            <span class="line-clamp-1 text-gray-700">{{ $book->title }}</span>
        </nav>

        <div class="mt-5 grid gap-8 lg:grid-cols-[320px_minmax(0,1fr)]">
            {{-- ===== Left: cover + actions ===== --}}
            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
                {{-- Cover --}}
                <div class="relative flex h-96 items-end justify-center overflow-hidden rounded-2xl border-t-2 border-blue-600 bg-blue-50">
                    <div class="absolute inset-0 opacity-40" style="background-image: repeating-linear-gradient(135deg, #ffffff 0 12px, #dbeafe 12px 24px);"></div>
                    @if ($book->type)
                        <span class="absolute left-3 top-3 rounded-md bg-white/90 px-2.5 py-1 text-xs font-semibold text-blue-700">{{ $book->type->name }}</span>
                    @endif
                    <span class="relative mb-3 text-[10px] uppercase tracking-wide text-blue-300">{{ __('muqova') }}</span>
                </div>

                {{-- Actions --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-4">
                    @if ($formats->isNotEmpty())
                        <div x-data="{ tab: 0 }" class="flex gap-1 rounded-lg border border-gray-200 p-1">
                            @foreach ($formats as $i => $format)
                                <button type="button" @click="tab = {{ $i }}"
                                        :class="tab === {{ $i }} ? 'bg-blue-50 text-blue-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                        class="flex-1 rounded-md px-3 py-2 text-sm font-medium transition">{{ $format->label() }}</button>
                            @endforeach
                        </div>
                    @endif

                    @if ($hasOnline)
                        <p class="mt-4 text-center text-xs text-gray-500">{{ __('To‘liq matn tizimga kirgan holda online o‘qiladi.') }}</p>
                        <a href="{{ route('read.book', $book->slug) }}" class="mt-3 flex items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-800">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" /></svg>
                            {{ auth('reader')->check() ? __('Online o‘qish') : __('Kirish va online o‘qish') }}
                        </a>
                        <p class="mt-2 text-center text-[11px] text-gray-400">{{ __('Faqat online o‘qish · Yuklab olish mavjud emas') }}</p>
                    @else
                        <p class="mt-4 rounded-lg bg-gray-50 px-3 py-3 text-center text-xs text-gray-500">{{ __('Bu nashr hozircha faqat bosma shaklda mavjud.') }}</p>
                    @endif

                    {{-- Availability --}}
                    <div class="mt-3 rounded-lg px-3 py-2.5 text-center text-sm font-medium {{ $available > 0 ? 'bg-green-50 text-green-700' : 'bg-gray-50 text-gray-500' }}">
                        @if ($available > 0)
                            <span class="mr-1.5 inline-block h-1.5 w-1.5 rounded-full bg-green-500"></span>
                            {{ __('ARMda :n nusxa mavjud', ['n' => $available]) }}
                        @else
                            {{ __('Hozircha ARMda mavjud emas') }}
                        @endif
                    </div>
                </div>

                {{-- Location note --}}
                <div class="flex gap-3 rounded-2xl border border-blue-100 bg-blue-50/50 p-4">
                    <svg class="h-5 w-5 flex-none text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                    <p class="text-xs leading-relaxed text-gray-600">
                        {{ __('Axborot resurs markazi o‘qish zalida joylashgan. Materiallar joyida yoki tizimga kirib online o‘qiladi.') }}
                    </p>
                </div>
            </aside>

            {{-- ===== Right: bibliographic record ===== --}}
            <div>
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    @if ($book->type)
                        <span class="font-semibold text-blue-700">{{ $book->type->name }}</span>
                    @endif
                    @if ($book->categories->isNotEmpty())
                        <span class="text-gray-300">·</span>
                        <span class="text-gray-500">{{ $book->categories->pluck('name')->join(' · ') }}</span>
                    @endif
                </div>

                <h1 class="mt-2 text-3xl font-extrabold leading-tight tracking-tight text-gray-900">{{ $book->title }}</h1>
                <p class="mt-2 text-base text-gray-500">
                    {{ $authors ?: '—' }}@if ($book->publication_year) · {{ $book->publication_year }}@endif
                </p>
                <div class="mt-2 flex items-center gap-1.5 text-sm text-gray-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                    {{ number_format($book->views_count ?? 0, 0, '.', ' ') }} {{ __('ko‘rish') }}
                </div>

                {{-- Bibliographic table --}}
                <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white">
                    <h2 class="border-b border-gray-100 px-5 py-3.5 text-sm font-bold text-gray-900">{{ __('Bibliografik ma‘lumotlar') }}</h2>
                    <dl class="divide-y divide-gray-100">
                        @foreach ($rows as [$label, $value])
                            <div class="grid grid-cols-1 gap-1 px-5 py-3 sm:grid-cols-[200px_1fr] sm:gap-4">
                                <dt class="text-sm text-gray-500">{{ $label }}</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $value }}</dd>
                            </div>
                        @endforeach

                        @if ($book->categories->isNotEmpty())
                            <div class="grid grid-cols-1 gap-1 px-5 py-3 sm:grid-cols-[200px_1fr] sm:gap-4">
                                <dt class="text-sm text-gray-500">{{ __('Kategoriyalar') }}</dt>
                                <dd class="flex flex-wrap gap-2">
                                    @foreach ($book->categories as $category)
                                        <a href="{{ route('catalog', ['categories' => [$category->id]]) }}"
                                           class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100">{{ $category->name }}</a>
                                    @endforeach
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>

                {{-- Annotation --}}
                @if (filled($book->annotation))
                    <div class="mt-8">
                        <h2 class="text-lg font-bold text-gray-900">{{ __('Annotatsiya') }}</h2>
                        <div class="mt-3 space-y-3 text-sm leading-relaxed text-gray-600">
                            @foreach (preg_split('/\r\n|\r|\n/', trim($book->annotation)) as $paragraph)
                                @if (trim($paragraph) !== '')
                                    <p>{{ $paragraph }}</p>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ===== Similar books ===== --}}
        @if ($similar->isNotEmpty())
            <section class="mt-14 border-t border-gray-200 pt-10">
                <div class="flex items-end justify-between">
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900">{{ __('O‘xshash kitoblar') }}</h2>
                    <a href="{{ route('catalog') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-800">{{ __('Barchasi →') }}</a>
                </div>
                <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($similar as $item)
                        <x-site.book-card :book="$item" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
