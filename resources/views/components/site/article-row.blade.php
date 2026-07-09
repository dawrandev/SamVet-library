@props([
    'article',
    'number' => null,   // optional ordinal shown in a leading badge
])

<div class="flex items-center gap-4 px-5 py-4">
    @if (! is_null($number))
        <span class="flex h-7 w-7 flex-none items-center justify-center rounded-full bg-blue-50 text-xs font-bold text-blue-700">{{ $number }}</span>
    @endif

    <div class="min-w-0 flex-1">
        <a href="{{ route('article.show', $article->slug) }}" class="line-clamp-1 text-sm font-semibold text-gray-900 transition hover:text-blue-700">{{ $article->title }}</a>
        <p class="mt-0.5 text-xs text-gray-500">
            {{ $article->author ?: '—' }}@if ($article->pages) · {{ $article->pages }}-{{ __('bet') }}@endif
        </p>
    </div>

    <a href="{{ route('article.show', $article->slug) }}" class="inline-flex flex-none items-center gap-1.5 rounded-lg border border-blue-200 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-50">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
        {{ __('Online o‘qish') }}
    </a>
</div>
