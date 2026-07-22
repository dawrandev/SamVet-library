@extends('layouts.site')

@section('title', __('Audiokitoblar'))

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <nav class="text-sm text-gray-500">
            <a href="{{ route('home') }}" class="hover:text-blue-700">{{ __('Bosh sahifa') }}</a>
            <span class="mx-1.5 text-gray-300">/</span>
            <a href="{{ route('sections') }}" class="hover:text-blue-700">{{ __('Bo‘limlar') }}</a>
            <span class="mx-1.5 text-gray-300">/</span>
            <span class="text-gray-700">{{ __('Audiokitoblar') }}</span>
        </nav>

        <div class="mt-3 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-gray-900">{{ __('Audiokitoblar') }}</h1>
                <p class="mt-1.5 text-sm text-gray-500">{{ __('Tinglash uchun tizimga kirish talab qilinadi.') }}</p>
            </div>
            <span class="rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700">
                {{ __(':n ta audiokitob', ['n' => number_format($audiobooks->total(), 0, '.', ' ')]) }}
            </span>
        </div>

        {{-- Search --}}
        <form method="GET" action="{{ route('audiobooks.index') }}" class="mt-6 flex max-w-md gap-2">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                   placeholder="{{ __('Nomi yoki muallifi bo‘yicha qidirish...') }}"
                   class="h-11 w-full rounded-lg border border-gray-200 px-4 text-sm text-gray-800 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
            <button type="submit" class="h-11 flex-none rounded-lg bg-blue-700 px-5 text-sm font-medium text-white transition hover:bg-blue-800">{{ __('Qidirish') }}</button>
        </form>

        @if ($audiobooks->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center">
                <p class="text-sm font-semibold text-gray-900">{{ __('Hozircha audiokitoblar yo‘q') }}</p>
                <p class="mt-1 text-sm text-gray-500">{{ __('Audiokitoblar qo‘shilgach shu yerda ko‘rinadi.') }}</p>
            </div>
        @else
            <div class="mt-7 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($audiobooks as $audiobook)
                    <x-site.audiobook-card :audiobook="$audiobook" />
                @endforeach
            </div>

            <div class="mt-10">
                {{ $audiobooks->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
@endsection
