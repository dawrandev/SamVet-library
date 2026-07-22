@extends('layouts.site')

@section('title', $video->name)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) $video->annotation), 160))

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <nav class="flex flex-wrap items-center gap-1.5 text-sm text-gray-500">
            <a href="{{ route('home') }}" class="hover:text-blue-700">{{ __('Bosh sahifa') }}</a>
            <span class="text-gray-300">/</span>
            <a href="{{ route('videos.index') }}" class="hover:text-blue-700">{{ __('Videolar') }}</a>
            <span class="text-gray-300">/</span>
            <span class="line-clamp-1 text-gray-700">{{ $video->name }}</span>
        </nav>

        <div class="mt-5 grid gap-8 lg:grid-cols-[320px_minmax(0,1fr)]">
            {{-- ===== Left: cover + actions ===== --}}
            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
                <div class="relative flex h-96 items-end justify-center overflow-hidden rounded-2xl border-t-2 border-blue-600 bg-blue-50">
                    @if ($video->cover_image)
                        <img src="{{ asset('storage/'.$video->cover_image) }}" alt="{{ $video->name }}" class="absolute inset-0 h-full w-full object-cover" />
                    @else
                        <div class="absolute inset-0 opacity-40" style="background-image: repeating-linear-gradient(135deg, #ffffff 0 12px, #dbeafe 12px 24px);"></div>
                        <svg class="relative mb-3 h-10 w-10 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                    @endif
                    <span class="absolute left-3 top-3 inline-flex items-center gap-1.5 rounded-md bg-white/90 px-2.5 py-1 text-xs font-semibold text-blue-700">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                        {{ __('Video') }}
                    </span>
                </div>

                {{-- Watch CTA --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-4">
                    @if ($video->tracks->isNotEmpty())
                        <p class="text-center text-xs text-gray-500">{{ __(':n ta video — tizimga kirgan holda online tomosha qilinadi.', ['n' => $video->tracks->count()]) }}</p>
                        <a href="{{ route('watch.video', $video->slug) }}" class="mt-3 flex items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-800">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                            {{ auth('reader')->check() ? __('Online tomosha qilish') : __('Kirish va online tomosha qilish') }}
                        </a>
                        <p class="mt-2 text-center text-[11px] text-gray-400">{{ __('Faqat online tomosha · Yuklab olish mavjud emas') }}</p>
                    @else
                        <p class="mt-1 rounded-lg bg-gray-50 px-3 py-3 text-center text-xs text-gray-500">{{ __('Bu videoga hali fayl qo‘shilmagan.') }}</p>
                    @endif
                </div>

                <div class="flex gap-3 rounded-2xl border border-blue-100 bg-blue-50/50 p-4">
                    <svg class="h-5 w-5 flex-none text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                    <p class="text-xs leading-relaxed text-gray-600">{{ __('Axborot resurs markazi tomonidan taqdim etiladi. Tomosha qilish faqat tizimga kirgan holda mumkin.') }}</p>
                </div>
            </aside>

            {{-- ===== Right: details ===== --}}
            <div>
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <span class="font-semibold text-blue-700">{{ __('Video') }}</span>
                    <span class="text-gray-500">
                        {{ number_format($video->views_count ?? 0, 0, '.', ' ') }} {{ __('ko‘rish') }}
                    </span>
                </div>

                <h1 class="mt-2 text-3xl font-extrabold leading-tight tracking-tight text-gray-900">{{ $video->name }}</h1>
                <p class="mt-2 text-base text-gray-500">{{ $video->author ?: '—' }}</p>

                {{-- Annotation --}}
                @if (filled($video->annotation))
                    <div class="mt-6">
                        <h2 class="text-lg font-bold text-gray-900">{{ __('Annotatsiya') }}</h2>
                        <div class="mt-3 space-y-3 text-sm leading-relaxed text-gray-600">
                            @foreach (preg_split('/\r\n|\r|\n/', trim($video->annotation)) as $paragraph)
                                @if (trim($paragraph) !== '')
                                    <p>{{ $paragraph }}</p>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Track list --}}
                @if ($video->tracks->isNotEmpty())
                    <div class="mt-8 overflow-hidden rounded-2xl border border-gray-200 bg-white">
                        <h2 class="border-b border-gray-100 px-5 py-3.5 text-sm font-bold text-gray-900">{{ __('Videolar ro‘yxati') }}</h2>
                        <ol class="divide-y divide-gray-100">
                            @foreach ($video->tracks as $i => $track)
                                <li class="flex items-center gap-3 px-5 py-3">
                                    <span class="flex h-7 w-7 flex-none items-center justify-center rounded-full bg-blue-50 text-xs font-semibold text-blue-700">{{ $i + 1 }}</span>
                                    <span class="text-sm text-gray-800">{{ $track->title }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif
            </div>
        </div>

        {{-- ===== Similar videos ===== --}}
        @if ($similar->isNotEmpty())
            <section class="mt-14 border-t border-gray-200 pt-10">
                <div class="flex items-end justify-between">
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900">{{ __('O‘xshash videolar') }}</h2>
                    <a href="{{ route('videos.index') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-800">{{ __('Barchasi →') }}</a>
                </div>
                <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($similar as $item)
                        <x-site.video-card :video="$item" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
