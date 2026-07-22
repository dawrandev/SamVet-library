@props(['audiobook'])

<a href="{{ route('audiobook.show', $audiobook->slug) }}" {{ $attributes->merge(['class' => 'group flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white transition hover:-translate-y-0.5 hover:shadow-lg']) }}>
    {{-- Cover --}}
    <div class="relative flex h-52 items-end justify-center overflow-hidden border-b-2 border-blue-600 bg-blue-50">
        @if ($audiobook->cover_image)
            <img src="{{ asset('storage/'.$audiobook->cover_image) }}" alt="{{ $audiobook->name }}" class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-105" />
        @else
            <div class="absolute inset-0 opacity-40" style="background-image: repeating-linear-gradient(135deg, #ffffff 0 10px, #dbeafe 10px 20px);"></div>
            <svg class="relative mb-3 h-8 w-8 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.5a.75.75 0 0 1-.75-.75v-6a.75.75 0 0 1 .75-.75h2.25Z" /></svg>
        @endif
        <span class="absolute left-2.5 top-2.5 inline-flex items-center gap-1 rounded-md bg-white/90 px-2 py-0.5 text-[11px] font-semibold text-blue-700">
            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.5a.75.75 0 0 1-.75-.75v-6a.75.75 0 0 1 .75-.75h2.25Z" /></svg>
            {{ __('Audiokitob') }}
        </span>
    </div>

    {{-- Body --}}
    <div class="flex flex-1 flex-col p-4">
        <h3 class="line-clamp-2 text-sm font-semibold text-gray-900 group-hover:text-blue-700">{{ $audiobook->name }}</h3>
        <p class="mt-1 text-xs text-gray-500">{{ $audiobook->author ?: '—' }}</p>

        <div class="mt-auto flex items-center gap-1.5 pt-3 text-xs text-gray-400">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 9l10.5-3m0 6.553v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 1 1-.99-3.467l2.31-.66a2.25 2.25 0 0 0 1.632-2.163Zm0 0V4.5A2.25 2.25 0 0 0 18 2.25h-1.5A2.25 2.25 0 0 0 15 4.5v9.053m0 0v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 1 1-.99-3.467l2.31-.66A2.25 2.25 0 0 0 15 13.553" /></svg>
            {{ __(':n ta audio', ['n' => $audiobook->tracks_count]) }}
        </div>
    </div>
</a>
