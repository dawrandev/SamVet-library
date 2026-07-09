@extends('layouts.site')

@section('title', __('Elektron katalog'))

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <nav class="text-sm text-gray-500">
            <a href="{{ route('home') }}" class="hover:text-blue-700">{{ __('Bosh sahifa') }}</a>
            <span class="mx-1.5 text-gray-300">/</span>
            <span class="text-gray-700">{{ __('Elektron katalog') }}</span>
        </nav>

        {{-- Title + result count --}}
        <div class="mt-3 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-gray-900">{{ __('Elektron katalog') }}</h1>
                <p class="mt-1.5 text-sm text-gray-500">{{ __('Universitet fondining to‘liq raqamli ro‘yxati — filtrlab qidiring.') }}</p>
            </div>
            <span class="rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700">
                {{ __(':n ta natija', ['n' => number_format($total, 0, '.', ' ')]) }}
            </span>
        </div>

        {{-- Everything below submits as one GET form (filters + sort share state) --}}
        <form method="GET" action="{{ route('catalog') }}" class="mt-7 grid gap-7 lg:grid-cols-[280px_minmax(0,1fr)]">
            {{-- Sidebar --}}
            <aside class="lg:sticky lg:top-24 lg:self-start">
                @include('partials.site.catalog-filters')
            </aside>

            {{-- Results --}}
            <div>
                {{-- Toolbar: sort + count --}}
                <div class="flex items-center justify-between gap-4 rounded-xl border border-gray-200 bg-white px-4 py-3">
                    <div class="flex items-center gap-2.5">
                        <label for="sort" class="text-sm text-gray-500">{{ __('Saralash:') }}</label>
                        <select id="sort" name="sort" onchange="this.form.submit()"
                                class="rounded-lg border border-gray-300 bg-white py-1.5 pl-3 pr-8 text-sm font-medium text-gray-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none">
                            @foreach ($sortOptions as $option)
                                <option value="{{ $option->value }}" @selected($filters->sort === $option)>{{ $option->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <span class="text-sm tabular-nums text-gray-400">{{ __(':n ta', ['n' => number_format($total, 0, '.', ' ')]) }}</span>
                </div>

                @if ($books->isEmpty())
                    {{-- Empty state --}}
                    <div class="mt-6 rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center">
                        <svg class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.34-4.34M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" /></svg>
                        <p class="mt-4 text-sm font-semibold text-gray-900">{{ __('Hech narsa topilmadi') }}</p>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Boshqa kalit so‘z yoki filtrlarni sinab ko‘ring.') }}</p>
                        @if ($filters->isActive())
                            <a href="{{ route('catalog') }}" class="mt-5 inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                {{ __('Filtrlarni tozalash') }}
                            </a>
                        @endif
                    </div>
                @else
                    <div class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($books as $book)
                            <x-site.book-card :book="$book" />
                        @endforeach
                    </div>

                    <div class="mt-8">
                        {{ $books->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>
        </form>
    </div>
@endsection
