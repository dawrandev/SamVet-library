@extends('layouts.site')

@section('title', __('Yangiliklar'))

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <nav class="text-sm text-gray-500">
            <a href="{{ route('home') }}" class="hover:text-blue-700">{{ __('Bosh sahifa') }}</a>
            <span class="mx-1.5 text-gray-300">/</span>
            <span class="text-gray-700">{{ __('Yangiliklar') }}</span>
        </nav>

        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-gray-900">{{ __('Yangiliklar') }}</h1>
        <p class="mt-1.5 text-sm text-gray-500">{{ __('Axborot resurs markazi tadbirlari, e‘lonlari va tanlovlari.') }}</p>

        {{-- Category filter chips --}}
        @if ($categories->isNotEmpty())
            <div class="mt-6 flex flex-wrap gap-2">
                <a href="{{ route('news.index') }}"
                   @class([
                       'rounded-full px-4 py-2 text-sm font-medium transition',
                       'bg-blue-700 text-white' => is_null($activeCategory),
                       'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50' => ! is_null($activeCategory),
                   ])>{{ __('Barchasi') }}</a>
                @foreach ($categories as $category)
                    <a href="{{ route('news.index', ['kategoriya' => $category['id']]) }}"
                       @class([
                           'rounded-full px-4 py-2 text-sm font-medium transition',
                           'bg-blue-700 text-white' => $activeCategory === $category['id'],
                           'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50' => $activeCategory !== $category['id'],
                       ])>{{ $category['label'] }}</a>
                @endforeach
            </div>
        @endif

        @if ($news->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center">
                <p class="text-sm font-semibold text-gray-900">{{ __('Hozircha yangiliklar yo‘q') }}</p>
                <p class="mt-1 text-sm text-gray-500">{{ __('Tez orada shu yerda e‘lonlar paydo bo‘ladi.') }}</p>
            </div>
        @else
            <div class="mt-7 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($news as $item)
                    <x-site.news-card :news="$item" />
                @endforeach
            </div>

            <div class="mt-10">
                {{ $news->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
@endsection
