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

        <div class="mt-6 grid gap-8 lg:grid-cols-[260px_minmax(0,1fr)]">
            {{-- Section sidebar --}}
            @if ($children->isNotEmpty())
                <aside class="lg:sticky lg:top-24 lg:self-start">
                    <nav class="overflow-hidden rounded-2xl border border-gray-200 bg-white p-2">
                        @foreach ($children as $child)
                            @php
                                $childTitle = $child->getTranslation('title', $locale, false) ?: $child->getTranslation('title', 'uz', false);
                                $active = $child->id === $item->id;
                                $external = $child->publicUrl() !== route('page.show', $child->id);
                            @endphp
                            <a href="{{ $child->publicUrl() }}"
                               @class([
                                   'flex items-center justify-between gap-2 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                                   'bg-blue-50 text-blue-700' => $active,
                                   'text-gray-600 hover:bg-gray-50 hover:text-gray-900' => ! $active,
                               ])>
                                {{ $childTitle }}
                                @if ($external)
                                    <svg class="h-4 w-4 flex-none text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                                @endif
                            </a>
                        @endforeach
                    </nav>
                </aside>
            @endif

            {{-- Content --}}
            <div @class(['lg:col-span-1', 'lg:col-start-1 lg:col-end-3' => $children->isEmpty()])>
                <div class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-8">
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
        </div>
    </div>
@endsection
