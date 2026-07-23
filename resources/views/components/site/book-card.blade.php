@props([
    'book',
    'badge' => null,   // optional corner label, e.g. "Yangi"
])

@php
    $authors = $book->authors->pluck('name')->join(', ');
    $available = $book->available_copies ?? null;
@endphp

<a href="{{ route('book.show', $book->slug) }}" {{ $attributes->merge(['class' => 'group flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white transition hover:-translate-y-0.5 hover:shadow-lg']) }}>
    {{-- Cover --}}
    <div class="relative flex aspect-[2/3] w-full items-end justify-center overflow-hidden border-b-2 border-blue-600 bg-blue-50">
        @if ($book->cover_image)
            <img src="{{ asset('storage/'.$book->cover_image) }}" alt="{{ $book->title }}" class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-105" />
        @else
            <div class="absolute inset-0 opacity-40" style="background-image: repeating-linear-gradient(135deg, #ffffff 0 10px, #dbeafe 10px 20px);"></div>
            <span class="relative mb-2 text-[10px] uppercase tracking-wide text-blue-300">{{ __('muqova') }}</span>
        @endif
        @if ($book->type)
            <span class="absolute left-2.5 top-2.5 rounded-md bg-white/90 px-2 py-0.5 text-[11px] font-semibold text-blue-700">{{ $book->type->name }}</span>
        @endif
        @if ($badge)
            <span class="absolute right-2.5 top-2.5 rounded-md bg-amber-400 px-2 py-0.5 text-[11px] font-semibold text-amber-950">{{ $badge }}</span>
        @endif
    </div>

    {{-- Body --}}
    <div class="flex flex-1 flex-col p-4">
        <h3 class="line-clamp-2 text-sm font-semibold text-gray-900 group-hover:text-blue-700">{{ $book->title }}</h3>
        <p class="mt-1 text-xs text-gray-500">{{ $authors ?: '—' }}@if ($book->publication_year) · {{ $book->publication_year }}@endif</p>

        <div class="mt-3 flex items-center gap-1.5 text-xs text-gray-400">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
            {{ number_format($book->views_count ?? 0, 0, '.', ' ') }} {{ __('ko‘rish') }}
        </div>

        @if (! is_null($available))
            <span class="mt-3 inline-flex w-fit items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">
                <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                {{ __('ARMda :n nusxa mavjud', ['n' => $available]) }}
            </span>
        @endif
    </div>
</a>
