@extends('layouts.site')

@php
    $locale = app()->getLocale();
    $sectionTitle = $section->getTranslation('title', $locale, false) ?: $section->getTranslation('title', 'uz', false);
    $itemTitle = $item->getTranslation('title', $locale, false) ?: $item->getTranslation('title', 'uz', false);
    $isSection = $item->id === $section->id;
@endphp

@section('title', $itemTitle)

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <nav class="flex flex-wrap items-center gap-1.5 text-sm text-gray-500">
            <a href="{{ route('home') }}" class="hover:text-blue-700">{{ __('Bosh sahifa') }}</a>
            <span class="text-gray-300">/</span>
            @if ($isSection)
                <span class="text-gray-700">{{ $sectionTitle }}</span>
            @else
                <a href="{{ route('page.show', $section->id) }}" class="hover:text-blue-700">{{ $sectionTitle }}</a>
                <span class="text-gray-300">/</span>
                <span class="line-clamp-1 text-gray-700">{{ $itemTitle }}</span>
            @endif
        </nav>

        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-gray-900">{{ $sectionTitle }}</h1>

        {{-- Content — full width, no competing section-nav sidebar. --}}
        <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 sm:p-8 lg:p-10">
            @unless ($isSection)
                <h2 class="text-xl font-bold text-gray-900">{{ $itemTitle }}</h2>
            @endunless

            @if ($item->page?->cover_image)
                <img src="{{ asset('storage/'.$item->page->cover_image) }}" alt="{{ $itemTitle }}" class="{{ $isSection ? '' : 'mt-5' }} aspect-[16/9] w-full rounded-xl object-cover" />
            @endif

            @if (filled($body))
                <div class="rich-text {{ $isSection ? '' : 'mt-5' }}">{!! $body !!}</div>
            @else
                <p class="mt-2 text-sm text-gray-500">{{ __('Sahifa matni tez orada qo‘shiladi.') }}</p>
            @endif
        </div>
    </div>
@endsection
