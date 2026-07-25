@extends('layouts.site')

@section('title', __('Kompyuterlardan foydalanish'))

@php
    // Literal Tailwind class strings (not built at runtime) so the JIT scanner keeps them.
    $statusStyles = [
        'success' => ['pill' => 'bg-green-50 text-green-700', 'screen' => 'fill-green-50', 'ring' => 'ring-green-100'],
        'error' => ['pill' => 'bg-red-50 text-red-700', 'screen' => 'fill-red-50', 'ring' => 'ring-red-100'],
        'warning' => ['pill' => 'bg-amber-50 text-amber-700', 'screen' => 'fill-amber-50', 'ring' => 'ring-amber-100'],
    ];
    $totalFree = $rooms->sum('freeCount');
    $totalAll = $rooms->sum('totalCount');
@endphp

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <nav class="flex items-center gap-1.5 text-sm text-gray-500">
                    <a href="{{ route('home') }}" class="hover:text-blue-700">{{ __('Bosh sahifa') }}</a>
                    <span class="text-gray-300">/</span>
                    <span class="text-gray-700">{{ __('Kompyuterlar') }}</span>
                </nav>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900">{{ __('Kompyuterlardan foydalanish') }}</h1>
                <p class="mt-1.5 max-w-xl text-sm text-gray-500">{{ __('Har bir xonadagi kompyuterlar va ularning holati — o‘rin izlab yurmang, oldindan bilib boring.') }}</p>
            </div>
            @if ($totalAll > 0)
                <div class="rounded-2xl border border-green-100 bg-green-50 px-5 py-3 text-center">
                    <p class="text-2xl font-extrabold text-green-700">{{ $totalFree }}<span class="text-base font-medium text-green-600">/{{ $totalAll }}</span></p>
                    <p class="text-xs font-medium text-green-700">{{ __('hozir bo‘sh') }}</p>
                </div>
            @endif
        </div>

        {{-- Legend --}}
        <div class="mt-6 flex flex-wrap gap-x-5 gap-y-2 rounded-xl border border-gray-200 bg-white px-4 py-3 text-xs text-gray-600">
            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-green-500"></span>{{ __('Bo‘sh') }}</span>
            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-red-500"></span>{{ __('Band') }}</span>
            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-gray-400"></span>{{ __('Nosoz') }}</span>
            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-500"></span>{{ __('Ta’mirda') }}</span>
        </div>

        @forelse ($rooms as $room)
            <section class="mt-8">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="flex items-center gap-2 text-lg font-bold text-gray-900">
                        <svg class="h-5 w-5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                        {{ $room['location']->label() }}
                    </h2>
                    <span class="text-xs font-medium text-gray-400">{{ $room['freeCount'] }}/{{ $room['totalCount'] }} {{ __('bo‘sh') }}</span>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    @foreach ($room['computers'] as $computer)
                        @php $style = $statusStyles[$computer->publicStatusColor()]; @endphp
                        <div class="flex flex-col items-center gap-3 rounded-2xl border border-gray-200 bg-white py-6 transition hover:-translate-y-0.5 hover:shadow-md">
                            <div class="relative">
                                <span class="absolute -left-2 -top-2 z-10 flex h-7 w-7 items-center justify-center rounded-full bg-gray-900 text-xs font-bold text-white ring-4 ring-white">{{ $computer->computer_number }}</span>
                                <svg class="h-20 w-24" viewBox="0 0 100 84" fill="none">
                                    {{-- Bezel --}}
                                    <rect x="6" y="6" width="88" height="56" rx="8" class="fill-gray-800" />
                                    {{-- Screen (tinted by status) --}}
                                    <rect x="14" y="14" width="72" height="40" rx="3" class="{{ $style['screen'] }}" />
                                    {{-- Camera notch --}}
                                    <circle cx="50" cy="10" r="1.6" class="fill-gray-600" />
                                    {{-- Stand --}}
                                    <rect x="42" y="64" width="16" height="9" class="fill-gray-400" />
                                    <rect x="24" y="75" width="52" height="6" rx="3" class="fill-gray-400" />
                                </svg>
                            </div>
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $style['pill'] }}">
                                @if ($computer->publicStatusColor() === 'error' && $computer->status->value === 'working')
                                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-red-500"></span>
                                @endif
                                {{ $computer->publicStatusLabel() }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="mt-10 rounded-2xl border border-gray-200 bg-white py-16 text-center">
                <svg class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="12" rx="2" /><path stroke-linecap="round" d="M8 20h8M12 16v4" /></svg>
                <p class="mt-3 text-sm text-gray-500">{{ __('Hozircha kompyuterlar haqida ma’lumot kiritilmagan.') }}</p>
            </div>
        @endforelse
    </div>
@endsection
