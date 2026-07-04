@extends('layouts.admin')

@section('title', __('Sayt menyusi'))

@section('content')
    {{-- Sarlavha --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ __('Sayt menyusi') }}</h2>
            <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">{{ __('Client sayt navbar navigatsiyasi — daraxtsimon menyular') }}</p>
        </div>
        <a href="{{ route('admin.menu-items.create') }}"
           class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
            <span class="text-lg leading-none">+</span> {{ __('Yangi menyu') }}
        </a>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
    @endif

    @forelse ($tree as $root)
        {{-- Har bir yuqori daraja menyu — alohida karta --}}
        <div class="mb-4 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            {{-- Ildiz qatori --}}
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 bg-gray-50/70 px-4 py-3.5 dark:border-gray-800 dark:bg-white/[0.02]">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex h-8 w-8 flex-none items-center justify-center rounded-lg bg-brand-50 text-brand-500 dark:bg-brand-500/15">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="truncate text-base font-semibold text-gray-800 dark:text-white/90">{{ $root->getTranslation('title', 'uz') }}</span>
                            @unless ($root->is_active)
                                <span class="text-theme-xs rounded-full bg-gray-100 px-2 py-0.5 font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ __('Nofaol') }}</span>
                            @endunless
                            @if ($root->target_blank)
                                <span class="text-gray-400" title="{{ __('Yangi oynada ochiladi') }}">&#8599;</span>
                            @endif
                        </div>
                        <span class="text-theme-xs truncate text-gray-400">{{ $root->url ?: __('havola belgilanmagan') }}</span>
                    </div>
                    @if ($root->children->isNotEmpty())
                        <span class="text-theme-xs flex-none rounded-full bg-gray-100 px-2 py-0.5 font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ $root->children->count() }} {{ __('ta') }}</span>
                    @endif
                </div>
                <x-admin.menu-item-actions :node="$root" />
            </div>

            {{-- Bolalar (daraxt) --}}
            @if ($root->children->isNotEmpty())
                <div class="py-2 pl-5 pr-3">
                    @foreach ($root->children as $child)
                        @include('pages.admin.menu-items.partials.tree-node', ['node' => $child])
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-4 py-16 text-center dark:border-gray-700 dark:bg-white/[0.03]">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Hozircha menyu elementlari yo‘q.') }}</p>
            <a href="{{ route('admin.menu-items.create') }}"
               class="text-brand-500 hover:text-brand-600 mt-2 inline-block text-sm font-medium">+ {{ __('Birinchi menyuni qo‘shing') }}</a>
        </div>
    @endforelse
@endsection
