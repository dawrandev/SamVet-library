@extends('layouts.site')

@php
    $locale = app()->getLocale();
    $title = $news->getTranslation('title', $locale, false) ?: $news->getTranslation('title', 'uz', false);
    $excerpt = $news->getTranslation('excerpt', $locale, false) ?: $news->getTranslation('excerpt', 'uz', false);
    $body = $news->getTranslation('body', $locale, false) ?: $news->getTranslation('body', 'uz', false);
    $category = $news->category?->getTranslation('name', $locale, false) ?: $news->category?->getTranslation('name', 'uz', false);
@endphp

@section('title', $title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) $excerpt), 160))

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        {{-- Breadcrumb --}}
        <nav class="flex flex-wrap items-center gap-1.5 text-sm text-gray-500">
            <a href="{{ route('home') }}" class="hover:text-blue-700">{{ __('Bosh sahifa') }}</a>
            <span class="text-gray-300">/</span>
            <a href="{{ route('news.index') }}" class="hover:text-blue-700">{{ __('Yangiliklar') }}</a>
            <span class="text-gray-300">/</span>
            <span class="line-clamp-1 text-gray-700">{{ $title }}</span>
        </nav>

        {{-- Meta --}}
        <div class="mt-5 flex flex-wrap items-center gap-3 text-sm">
            @if ($category)
                <span class="rounded-md bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">{{ $category }}</span>
            @endif
            <span class="text-gray-400">{{ $news->published_at?->translatedFormat('d.m.Y') }}</span>
            <span class="flex items-center gap-1.5 text-gray-400">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                {{ number_format($news->views_count ?? 0, 0, '.', ' ') }}
            </span>
        </div>

        <h1 class="mt-3 text-3xl font-extrabold leading-tight tracking-tight text-gray-900 sm:text-4xl">{{ $title }}</h1>
        @if ($excerpt)
            <p class="mt-4 text-lg leading-relaxed text-gray-600">{{ $excerpt }}</p>
        @endif

        {{-- Cover --}}
        @if ($news->cover_image)
            <img src="{{ asset('storage/'.$news->cover_image) }}" alt="{{ $title }}" class="mt-6 aspect-[16/9] w-full rounded-2xl object-cover" />
        @endif

        {{-- Body (admin rich-text; authored by librarians) --}}
        @if (filled($body))
            <div class="news-body mt-8">{!! $body !!}</div>
        @endif

        {{-- Gallery --}}
        @if ($news->images->isNotEmpty())
            <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3">
                @foreach ($news->images as $image)
                    <img src="{{ asset('storage/'.$image->path) }}" alt="" class="aspect-square w-full rounded-xl object-cover" />
                @endforeach
            </div>
        @endif

        <div class="mt-10 border-t border-gray-100 pt-6">
            <a href="{{ route('news.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-700 hover:text-blue-800">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                {{ __('Barcha yangiliklar') }}
            </a>
        </div>
    </article>

    {{-- Related --}}
    @if ($related->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">{{ __('Boshqa yangiliklar') }}</h2>
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($related as $item)
                    <x-site.news-card :news="$item" />
                @endforeach
            </div>
        </section>
    @endif
@endsection
