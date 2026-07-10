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
            <img src="{{ asset('images/samvet/logo.png') }}" alt="{{ __('SamVMChBTU Nukus filiali logotipi') }}"
                 class="h-12 w-12 flex-none object-contain" width="48" height="48" />
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
            @auth('reader')
                @php $reader = auth('reader')->user(); @endphp
                <div x-data="{ open: false }" class="relative hidden sm:block">
                    <button type="button" @click="open = ! open" class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        <svg class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                        <span class="max-w-[10rem] truncate">{{ \Illuminate\Support\Str::of($reader->full_name)->explode(' ')->first() }}</span>
                    </button>

                    <div x-show="open" x-cloak @click.outside="open = false"
                         class="absolute right-0 z-50 mt-2 w-60 rounded-xl border border-gray-200 bg-white p-2 shadow-lg">
                        <div class="border-b border-gray-100 px-3 pb-2.5 pt-1.5">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ $reader->full_name }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ $reader->id_number }}</p>
                        </div>
                        <form method="POST" action="{{ route('reader.logout') }}" class="pt-1">
                            @csrf
                            <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-red-600 transition hover:bg-red-50">{{ __('Chiqish') }}</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('reader.login') }}" class="hidden items-center gap-2 rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-800 sm:inline-flex">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                    {{ __('Kirish') }}
                </a>
            @endauth

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
            @auth('reader')
                <div class="mt-2 border-t border-gray-100 pt-2">
                    <p class="px-3 py-1 text-xs text-gray-500">{{ auth('reader')->user()->id_number }}</p>
                    <form method="POST" action="{{ route('reader.logout') }}">
                        @csrf
                        <button type="submit" class="w-full rounded-lg px-3 py-2.5 text-left text-sm font-medium text-red-600">{{ __('Chiqish') }}</button>
                    </form>
                </div>
            @else
                <a href="{{ route('reader.login') }}" class="mt-1 inline-flex items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white">{{ __('Kirish') }}</a>
            @endauth
        </nav>
    </div>
</header>
