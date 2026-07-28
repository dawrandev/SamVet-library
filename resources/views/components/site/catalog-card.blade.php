@props([
    'item',            // App\Data\CatalogItem
    'badge' => null,   // optional corner label, e.g. "Yangi"
])

@php
    $type = $item->type;
@endphp

<a href="{{ $item->url() }}" {{ $attributes->merge(['class' => 'group flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white transition hover:-translate-y-0.5 hover:shadow-lg']) }}>
    {{-- Cover — dissertations/avtoreferats have no cover_image column at all,
         so they always fall through to the placeholder below. --}}
    <div class="relative flex aspect-[2/3] w-full items-end justify-center overflow-hidden border-b-2 border-blue-600 bg-blue-50">
        @if ($item->coverImage)
            <img src="{{ asset('storage/'.$item->coverImage) }}" alt="{{ $item->title }}" class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-105" />
        @else
            <div class="absolute inset-0 opacity-40" style="background-image: repeating-linear-gradient(135deg, #ffffff 0 10px, #dbeafe 10px 20px);"></div>
            @switch($type)
                @case(\App\Enums\CatalogResourceType::Audiobook)
                    <svg class="relative mb-2 h-8 w-8 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.5a.75.75 0 0 1-.75-.75v-6a.75.75 0 0 1 .75-.75h2.25Z" /></svg>
                    @break
                @case(\App\Enums\CatalogResourceType::Video)
                    <svg class="relative mb-2 h-8 w-8 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                    @break
                @case(\App\Enums\CatalogResourceType::Dissertation)
                @case(\App\Enums\CatalogResourceType::Avtoreferat)
                    <svg class="relative mb-2 h-8 w-8 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                    @break
                @default
                    <span class="relative mb-2 text-[10px] uppercase tracking-wide text-blue-300">{{ __('muqova') }}</span>
            @endswitch
        @endif

        <span class="absolute left-2.5 top-2.5 rounded-md bg-white/90 px-2 py-0.5 text-[11px] font-semibold text-blue-700">
            {{ $item->badgeLabel() }}
        </span>
        @if ($badge)
            <span class="absolute right-2.5 top-2.5 rounded-md bg-amber-400 px-2 py-0.5 text-[11px] font-semibold text-amber-950">{{ $badge }}</span>
        @endif
    </div>

    {{-- Body --}}
    <div class="flex flex-1 flex-col p-4">
        <h3 class="line-clamp-2 text-sm font-semibold text-gray-900 group-hover:text-blue-700">{{ $item->title }}</h3>
        <p class="mt-1 text-xs text-gray-500">{{ $item->author ?: '—' }}@if ($item->year) · {{ $item->year }}@endif</p>

        @if ($item->formats !== [])
            {{-- Books: physical/electronic/braille copy pills --}}
            <div class="mt-2 flex flex-wrap gap-1">
                @foreach ($item->formats as $format)
                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600">{{ $format->label() }}</span>
                @endforeach
            </div>
        @elseif ($item->hasFile)
            {{-- Dissertation/avtoreferat: PDF-only, always "Elektron" --}}
            <div class="mt-2">
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600">{{ __('Elektron') }}</span>
            </div>
        @endif

        @if (! is_null($item->trackCount))
            <p class="mt-2 text-xs text-gray-400">
                {{ $type === \App\Enums\CatalogResourceType::Video
                    ? __(':n ta video', ['n' => $item->trackCount])
                    : __(':n ta audio', ['n' => $item->trackCount]) }}
            </p>
        @endif

        <div class="mt-3 flex items-center gap-1.5 text-xs text-gray-400">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
            {{ number_format($item->viewsCount, 0, '.', ' ') }} {{ __('ko‘rish') }}
        </div>

        @auth('reader')
            @if (! is_null($item->availableCopies))
                <span class="mt-3 inline-flex w-fit items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                    {{ __('ARMda :n nusxa mavjud', ['n' => $item->availableCopies]) }}
                </span>
            @endif
        @endauth
    </div>
</a>
