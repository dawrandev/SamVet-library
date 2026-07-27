@extends('layouts.site')

@section('title', $avtoreferat->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) $avtoreferat->annotation), 160))

@php
    // Admin-only fields (inventory_number, condition) are intentionally excluded —
    // never shown on the public site, same rule as every other material type.
    $rows = array_filter([
        [__('Muallifi'), $avtoreferat->author],
        [__('Ixtisoslik shifri va nomi'), $avtoreferat->specialty],
        [__('Fan nomi'), $avtoreferat->scienceField?->name],
        [__('Turi'), $avtoreferat->degree?->label()],
        [__('Ilmiy rahbar'), $avtoreferat->advisor],
        [__('Ilmiy kengash raqami'), $avtoreferat->council_number],
        [__('Dissertatsiya himoya muassasi'), $avtoreferat->defense_institution],
        [__('Dissertatsiya bajarilgan muassasi'), $avtoreferat->performed_institution],
        ['UO‘K', $avtoreferat->udc],
        [__('Ro‘yxat raqami'), $avtoreferat->registration_number],
        [__('Nashr joyi'), $avtoreferat->publicationPlace?->name],
        [__('Himoya yili'), $avtoreferat->defense_year],
        [__('Tillari'), $avtoreferat->languages->pluck('name')->implode(', ') ?: null],
        [__('Tayanch so‘zlar'), $avtoreferat->keywords],
    ], fn ($row) => filled($row[1]));
@endphp

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <nav class="flex flex-wrap items-center gap-1.5 text-sm text-gray-500">
            <a href="{{ route('home') }}" class="hover:text-blue-700">{{ __('Bosh sahifa') }}</a>
            <span class="text-gray-300">/</span>
            <a href="{{ route('avtoreferats.index') }}" class="hover:text-blue-700">{{ __('Avtoreferatlar') }}</a>
            <span class="text-gray-300">/</span>
            <span class="line-clamp-1 text-gray-700">{{ $avtoreferat->title }}</span>
        </nav>

        <div class="mt-5 grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px]">
            {{-- ===== Main ===== --}}
            <div>
                <span class="text-sm font-semibold text-blue-700">{{ __('Avtoreferat') }}</span>

                <h1 class="mt-2 text-3xl font-extrabold leading-tight tracking-tight text-gray-900">{{ $avtoreferat->title }}</h1>
                @if ($avtoreferat->author)
                    <p class="mt-2 text-base text-gray-500">{{ $avtoreferat->author }}</p>
                @endif

                {{-- Record --}}
                <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white">
                    <h2 class="border-b border-gray-100 px-5 py-3.5 text-sm font-bold text-gray-900">{{ __('Avtoreferat ma’lumotlari') }}</h2>
                    <dl class="divide-y divide-gray-100">
                        @foreach ($rows as [$label, $value])
                            <div class="grid grid-cols-1 gap-1 px-5 py-3 sm:grid-cols-[220px_1fr] sm:gap-4">
                                <dt class="text-sm text-gray-500">{{ $label }}</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                {{-- Annotation --}}
                @if (filled($avtoreferat->annotation))
                    <div class="mt-8">
                        <h2 class="text-lg font-bold text-gray-900">{{ __('Annotatsiya') }}</h2>
                        <div class="mt-3 space-y-3 text-sm leading-relaxed text-gray-600">
                            @foreach (preg_split('/\r\n|\r|\n/', trim($avtoreferat->annotation)) as $paragraph)
                                @if (trim($paragraph) !== '')
                                    <p>{{ $paragraph }}</p>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- ===== Sidebar ===== --}}
            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
                {{-- Full text --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-5">
                    <h2 class="text-sm font-bold text-gray-900">{{ __('To‘liq matn') }}</h2>
                    @if ($hasOnline)
                        <p class="mt-1.5 text-xs leading-relaxed text-gray-500">{{ __('Avtoreferat to‘liq matni tizimga kirgan holda online o‘qiladi.') }}</p>
                        <a href="{{ route('read.avtoreferat', $avtoreferat->slug) }}" class="mt-4 flex items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-800">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                            {{ __('Online o‘qish') }}
                        </a>
                        <p class="mt-2 text-center text-[11px] text-gray-400">{{ __('Faqat online o‘qish · Yuklab olish mavjud emas') }}</p>
                    @else
                        <p class="mt-2 rounded-lg bg-gray-50 px-3 py-3 text-center text-xs text-gray-500">{{ __('To‘liq matn hozircha mavjud emas.') }}</p>
                    @endif
                </div>

                {{-- Location note --}}
                <div class="flex gap-3 rounded-2xl border border-blue-100 bg-blue-50/50 p-4">
                    <svg class="h-5 w-5 flex-none text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                    <p class="text-xs leading-relaxed text-gray-600">{{ __('Axborot resurs markazi o‘qish zalida joylashgan. Matn joyida yoki tizimga kirib online o‘qiladi.') }}</p>
                </div>
            </aside>
        </div>
    </div>
@endsection
