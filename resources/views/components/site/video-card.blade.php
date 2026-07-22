@props(['video'])

<a href="{{ route('video.show', $video->slug) }}" {{ $attributes->merge(['class' => 'group flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white transition hover:-translate-y-0.5 hover:shadow-lg']) }}>
    {{-- Cover --}}
    <div class="relative flex h-52 items-end justify-center overflow-hidden border-b-2 border-blue-600 bg-blue-50">
        @if ($video->cover_image)
            <img src="{{ asset('storage/'.$video->cover_image) }}" alt="{{ $video->name }}" class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-105" />
        @else
            <div class="absolute inset-0 opacity-40" style="background-image: repeating-linear-gradient(135deg, #ffffff 0 10px, #dbeafe 10px 20px);"></div>
            <svg class="relative mb-3 h-8 w-8 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
        @endif
        <span class="absolute left-2.5 top-2.5 inline-flex items-center gap-1 rounded-md bg-white/90 px-2 py-0.5 text-[11px] font-semibold text-blue-700">
            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
            {{ __('Video') }}
        </span>
    </div>

    {{-- Body --}}
    <div class="flex flex-1 flex-col p-4">
        <h3 class="line-clamp-2 text-sm font-semibold text-gray-900 group-hover:text-blue-700">{{ $video->name }}</h3>
        <p class="mt-1 text-xs text-gray-500">{{ $video->author ?: '—' }}</p>

        <div class="mt-auto flex items-center gap-1.5 pt-3 text-xs text-gray-400">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
            {{ __(':n ta video', ['n' => $video->tracks_count]) }}
        </div>
    </div>
</a>
