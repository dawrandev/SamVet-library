@extends('layouts.site')

@section('title', $title)

@section('content')
    {{-- data-reader wraps BOTH the toolbar and the document: the viewer script
         looks up its controls inside this element. --}}
    <div data-reader="{{ $fileUrl }}" class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-t-2xl border border-gray-200 bg-white px-4 py-3">
            <div class="flex min-w-0 items-center gap-3">
                <a href="{{ $backUrl }}" class="flex h-9 w-9 flex-none items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50" aria-label="{{ __('Orqaga') }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                </a>
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-gray-900">{{ $title }}</p>
                    @if ($subtitle)
                        <p class="truncate text-xs text-gray-500">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-4">
                {{-- Page navigation --}}
                <div class="flex items-center gap-1.5">
                    <button type="button" data-reader-prev class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40" aria-label="{{ __('Oldingi sahifa') }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                    </button>
                    <div class="flex items-center gap-1.5 text-sm text-gray-500">
                        <input type="number" data-reader-page value="1" min="1"
                               class="h-9 w-14 rounded-lg border border-gray-200 text-center text-sm text-gray-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
                               aria-label="{{ __('Sahifa raqami') }}" />
                        <span>/</span>
                        <span data-reader-total class="tabular-nums">—</span>
                    </div>
                    <button type="button" data-reader-next class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40" aria-label="{{ __('Keyingi sahifa') }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                    </button>
                </div>

                {{-- Zoom --}}
                <div class="flex items-center gap-1.5">
                    <button type="button" data-reader-zoom-out class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40" aria-label="{{ __('Kichraytirish') }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M5 12h14" /></svg>
                    </button>
                    <span data-reader-zoom-label class="w-12 text-center text-sm tabular-nums text-gray-500">100%</span>
                    <button type="button" data-reader-zoom-in class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40" aria-label="{{ __('Kattalashtirish') }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M12 5v14M5 12h14" /></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Document --}}
        <div class="overflow-hidden rounded-b-2xl border border-t-0 border-gray-200 bg-gray-100">
            <div data-reader-viewport class="relative flex h-[calc(100vh-15rem)] min-h-[28rem] items-start justify-center overflow-auto p-4">
                {{-- Loading --}}
                <div data-reader-spinner class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-gray-400">
                    <svg class="h-7 w-7 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" /><path class="opacity-80" d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg>
                    <p class="text-sm">{{ __('Hujjat yuklanmoqda...') }}</p>
                </div>

                {{-- Failure --}}
                <div data-reader-error class="absolute inset-0 hidden flex-col items-center justify-center gap-2 px-6 text-center">
                    <p class="text-sm font-semibold text-gray-900">{{ __('Hujjatni ochib bo‘lmadi') }}</p>
                    <p class="text-sm text-gray-500">{{ __('Sahifani yangilang yoki kutubxonaga murojaat qiling.') }}</p>
                </div>

                <canvas data-reader-canvas class="invisible bg-white shadow-lg"></canvas>
            </div>
        </div>

        <p class="mt-3 text-center text-xs text-gray-400">{{ __('Faqat online o‘qish · Yuklab olish mavjud emas') }}</p>
    </div>
@endsection
