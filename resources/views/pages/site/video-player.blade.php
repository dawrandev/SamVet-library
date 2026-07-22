@extends('layouts.site')

@section('title', $video->name)

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8"
         x-data="{
             current: {{ $video->tracks->first()?->id ?? 'null' }},
             tracks: @js($video->tracks->map(fn ($t) => ['id' => $t->id, 'title' => $t->title, 'url' => route('watch.video.file', [$video->slug, $t->id])])),
             get currentTrack() { return this.tracks.find(t => t.id === this.current) },
             play(id) {
                 this.current = id;
                 this.$nextTick(() => { this.$refs.player.load(); this.$refs.player.play(); });
             },
         }"
    >
        {{-- Header --}}
        <div class="flex items-center gap-3">
            <a href="{{ $backUrl }}" class="flex h-9 w-9 flex-none items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50" aria-label="{{ __('Orqaga') }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            </a>
            <div class="min-w-0">
                <p class="truncate text-base font-bold text-gray-900">{{ $video->name }}</p>
                @if ($video->author)
                    <p class="truncate text-xs text-gray-500">{{ $video->author }}</p>
                @endif
            </div>
        </div>

        {{-- Player --}}
        <div class="mt-5 overflow-hidden rounded-2xl border border-gray-200 bg-black">
            {{-- controlsList="nodownload" only hides the browser's own download button —
                 the real protection is server-side (reader.auth + no direct file URL). --}}
            <video x-ref="player" controls controlsList="nodownload" class="aspect-video w-full bg-black" :src="currentTrack?.url"></video>
        </div>
        <p class="mt-2 truncate text-sm font-semibold text-gray-900" x-text="currentTrack?.title"></p>

        {{-- Track list --}}
        <div class="mt-5 overflow-hidden rounded-2xl border border-gray-200 bg-white">
            <h2 class="border-b border-gray-100 px-5 py-3.5 text-sm font-bold text-gray-900">{{ __('Videolar') }}</h2>
            <ol class="divide-y divide-gray-100">
                <template x-for="(track, i) in tracks" :key="track.id">
                    <li>
                        <button type="button" @click="play(track.id)"
                                class="flex w-full items-center gap-3 px-5 py-3 text-left transition hover:bg-gray-50"
                                :class="current === track.id && 'bg-blue-50/60'">
                            <span class="flex h-7 w-7 flex-none items-center justify-center rounded-full text-xs font-semibold"
                                  :class="current === track.id ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700'"
                                  x-text="i + 1"></span>
                            <span class="text-sm" :class="current === track.id ? 'font-semibold text-blue-700' : 'text-gray-800'" x-text="track.title"></span>
                        </button>
                    </li>
                </template>
            </ol>
        </div>

        <p class="mt-3 text-center text-xs text-gray-400">{{ __('Faqat online tomosha · Yuklab olish mavjud emas') }}</p>
    </div>
@endsection
