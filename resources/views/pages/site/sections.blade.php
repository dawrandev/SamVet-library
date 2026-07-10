@extends('layouts.site')

@section('title', __('Bo‘limlar'))

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <nav class="text-sm text-gray-500">
            <a href="{{ route('home') }}" class="hover:text-blue-700">{{ __('Bosh sahifa') }}</a>
            <span class="mx-1.5 text-gray-300">/</span>
            <span class="text-gray-700">{{ __('Bo‘limlar') }}</span>
        </nav>

        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-gray-900">{{ __('Elektron kutubxona bo‘limlari') }}</h1>
        <p class="mt-1.5 text-sm text-gray-500">{{ __('Fond tarkibi — bo‘limni tanlab, unga tegishli manbalarni ko‘ring.') }}</p>

        <div class="mt-7 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($tiles as $tile)
                <a href="{{ $tile['url'] }}" class="group rounded-2xl border border-gray-200 bg-white p-5 transition hover:border-blue-300 hover:shadow-md">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" /></svg>
                    </span>
                    <h2 class="mt-4 font-semibold text-gray-900 group-hover:text-blue-700">{{ $tile['label'] }}</h2>
                    <p class="mt-0.5 text-xs text-gray-400">{{ number_format($tile['count'], 0, '.', ' ') }} {{ __('ta manba') }}</p>
                </a>
            @endforeach

            <a href="{{ route('catalog') }}" class="flex flex-col justify-between rounded-2xl bg-blue-700 p-5 text-white transition hover:bg-blue-800">
                <h2 class="font-semibold">{{ __('Barcha resurslar katalogi') }}</h2>
                <span class="mt-6 text-sm font-medium text-blue-100">{{ __('Katalogga o‘tish') }} →</span>
            </a>
        </div>
    </div>
@endsection
