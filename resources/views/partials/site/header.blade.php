@php
    // "ARM haqida" links into the first content section (its first child page,
    // or the section landing). $armSection comes from a view composer.
    $armChild = $armSection?->children->first();
    $armUrl = $armChild
        ? $armChild->publicUrl()
        : ($armSection ? route('page.show', $armSection->id) : '#');

    // Primary navigation. `#` marks a target not built yet.
    $nav = [
        ['label' => __('Bosh sahifa'), 'url' => route('home'), 'active' => request()->routeIs('home')],
        ['label' => __('Elektron katalog'), 'url' => route('catalog'), 'active' => request()->routeIs('catalog')],
        ['label' => __('Bo‘limlar'), 'url' => '#', 'active' => false],
        ['label' => __('Yangiliklar'), 'url' => route('news.index'), 'active' => request()->routeIs('news.*')],
        ['label' => __('ARM haqida'), 'url' => $armUrl, 'active' => request()->routeIs('page.show')],
    ];
@endphp

<header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-gray-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3.5 sm:px-6 lg:px-8">
        {{-- Brand --}}
        <a href="{{ route('home') }}" class="flex items-center gap-3">
            <span class="flex h-11 w-11 flex-none items-center justify-center rounded-xl bg-blue-700 text-white">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                </svg>
            </span>
            <span class="leading-tight">
                <span class="block text-base font-bold text-gray-900">SamVMChBTU · {{ __('Nukus filiali') }}</span>
                <span class="block text-xs text-gray-500">{{ __('Axborot resurs markazi (ARM)') }}</span>
            </span>
        </a>

        {{-- Desktop nav --}}
        <nav class="hidden items-center gap-1 lg:flex">
            @foreach ($nav as $item)
                <a href="{{ $item['url'] }}"
                   @class([
                       'rounded-lg px-3.5 py-2 text-sm font-medium transition',
                       'text-blue-700' => $item['active'],
                       'text-gray-600 hover:bg-gray-50 hover:text-gray-900' => ! $item['active'],
                   ])>{{ $item['label'] }}</a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            <a href="#" class="hidden items-center gap-2 rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-800 sm:inline-flex">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                {{ __('Kirish') }}
            </a>

            {{-- Mobile toggle --}}
            <button type="button" @click="open = !open" class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-600 lg:hidden" aria-label="{{ __('Menyu') }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
            </button>
        </div>
    </div>

    {{-- Mobile nav --}}
    <div x-show="open" x-cloak class="border-t border-gray-100 lg:hidden">
        <nav class="mx-auto flex max-w-7xl flex-col gap-1 px-4 py-3 sm:px-6">
            @foreach ($nav as $item)
                <a href="{{ $item['url'] }}"
                   @class([
                       'rounded-lg px-3 py-2.5 text-sm font-medium',
                       'bg-blue-50 text-blue-700' => $item['active'],
                       'text-gray-700 hover:bg-gray-50' => ! $item['active'],
                   ])>{{ $item['label'] }}</a>
            @endforeach
            <a href="#" class="mt-1 inline-flex items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white">{{ __('Kirish') }}</a>
        </nav>
    </div>
</header>
