@props(['news'])

@php
    $locale = app()->getLocale();
    $title = $news->getTranslation('title', $locale, false) ?: $news->getTranslation('title', 'uz', false);
    $excerpt = $news->getTranslation('excerpt', $locale, false) ?: $news->getTranslation('excerpt', 'uz', false);
    $category = $news->category?->getTranslation('name', $locale, false) ?: $news->category?->getTranslation('name', 'uz', false);
    $url = route('news.show', $news->slug);
@endphp

<article {{ $attributes->merge(['class' => 'group flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white transition hover:-translate-y-0.5 hover:shadow-lg']) }}>
    <a href="{{ $url }}" class="relative block h-48 overflow-hidden bg-blue-50">
        @if ($news->cover_image)
            <img src="{{ asset('storage/'.$news->cover_image) }}" alt="{{ $title }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" />
        @else
            <div class="absolute inset-0 opacity-40" style="background-image: repeating-linear-gradient(135deg, #ffffff 0 12px, #dbeafe 12px 24px);"></div>
            <span class="absolute bottom-2 right-3 text-[10px] uppercase tracking-wide text-blue-300">{{ __('rasm') }}</span>
        @endif
        @if ($category)
            <span class="absolute left-3 top-3 rounded-md bg-white/90 px-2.5 py-1 text-xs font-semibold text-blue-700">{{ $category }}</span>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-5">
        <a href="{{ $url }}">
            <h3 class="line-clamp-2 text-base font-bold text-gray-900 transition group-hover:text-blue-700">{{ $title }}</h3>
        </a>
        @if ($excerpt)
            <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-gray-500">{{ $excerpt }}</p>
        @endif

        <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 text-sm">
            <span class="text-gray-400">{{ $news->published_at?->translatedFormat('d.m.Y') }}</span>
            <a href="{{ $url }}" class="font-semibold text-blue-700 hover:text-blue-800">{{ __('Davomini o‘qish →') }}</a>
        </div>
    </div>
</article>
