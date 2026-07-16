@extends('layouts.admin')

@php
    $menuTitle = $menuItem->getTranslation('title', 'uz');
    $titles = $page ? $page->getTranslations('title') : [];
    $bodies = $page ? $page->getTranslations('body') : [];
@endphp

@section('title', __('Sahifa') . ' — ' . $menuTitle)

@section('content')
    @php
        $locales = ['uz' => 'O‘zbekcha', 'ru' => 'Русский', 'kk' => 'Qaraqalpaqsha'];
        $available = array_keys(array_filter($bodies, static fn ($v) => trim((string) $v) !== ''));
        if ($available === []) {
            $available = ['uz'];
        }
        $default = $available[0];

        $prose = 'leading-relaxed text-gray-700 dark:text-gray-300 '
            .'[&_h1]:mb-3 [&_h1]:mt-6 [&_h1]:text-2xl [&_h1]:font-bold [&_h1]:text-gray-800 dark:[&_h1]:text-white/90 '
            .'[&_h2]:mb-3 [&_h2]:mt-6 [&_h2]:text-xl [&_h2]:font-bold [&_h2]:text-gray-800 dark:[&_h2]:text-white/90 '
            .'[&_h3]:mb-2 [&_h3]:mt-5 [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:text-gray-800 dark:[&_h3]:text-white/90 '
            .'[&_p]:mb-4 [&_ul]:mb-4 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:mb-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_li]:mb-1 '
            .'[&_a]:text-brand-500 [&_a]:underline [&_img]:my-4 [&_img]:rounded-lg [&_img]:max-w-full '
            .'[&_blockquote]:my-4 [&_blockquote]:border-l-4 [&_blockquote]:border-brand-300 [&_blockquote]:pl-4 [&_blockquote]:italic [&_blockquote]:text-gray-500 '
            .'[&_table]:my-4 [&_table]:w-full [&_td]:border [&_td]:border-gray-200 [&_td]:p-2 [&_th]:border [&_th]:border-gray-200 [&_th]:p-2 dark:[&_td]:border-gray-700 dark:[&_th]:border-gray-700 '
            .'[&_iframe]:my-4 [&_iframe]:aspect-video [&_iframe]:w-full [&_iframe]:rounded-lg';
    @endphp

    <div x-data="{ loc: '{{ $default }}' }">
        {{-- Header + actions --}}
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.menu-items.index') }}"
                   class="flex h-9 w-9 flex-none items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
                <div>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ __('Sahifani ko‘rish') }}</h2>
                    <p class="text-theme-sm text-gray-500 dark:text-gray-400">
                        {{ __('Menyu') }}: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $menuTitle }}</span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.menu-items.page.edit', $menuItem) }}"
                   class="text-theme-sm inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5">
                    <x-admin.icon name="document-text" class="h-4 w-4" /> {{ __('Tahrirlash') }}
                </a>
            </div>
        </div>

        @if (! $page)
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-4 py-16 text-center dark:border-gray-700 dark:bg-white/[0.03]">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Bu menyu uchun sahifa matni hali kiritilmagan.') }}</p>
                <a href="{{ route('admin.menu-items.page.edit', $menuItem) }}" class="text-brand-500 hover:text-brand-600 mt-2 inline-block text-sm font-medium">+ {{ __('Matn kiritish') }}</a>
            </div>
        @else
            {{-- Language tabs (only locales with body content) --}}
            @if (count($available) > 1)
                <div class="mb-5 inline-flex items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-gray-900">
                    @foreach ($available as $code)
                        <button type="button" @click="loc = '{{ $code }}'"
                                :class="loc === '{{ $code }}' ? 'shadow-theme-xs bg-white text-gray-900 dark:bg-gray-800 dark:text-white' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
                                class="text-theme-sm rounded-md px-4 py-2 font-medium transition">{{ $locales[$code] }}</button>
                    @endforeach
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {{-- Main: content per locale --}}
                <div class="lg:col-span-2">
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03] md:p-8">
                        @foreach ($available as $code)
                            <div x-show="loc === '{{ $code }}'" @if (! $loop->first) x-cloak @endif>
                                <h1 class="mb-3 text-2xl font-bold text-gray-800 dark:text-white/90">{{ ($titles[$code] ?? '') ?: $menuTitle }}</h1>

                                {{-- Body is sanitized with HTMLPurifier on save — safe to render as HTML. --}}
                                @if (trim((string) ($bodies[$code] ?? '')) !== '')
                                    <div class="{{ $prose }}">
                                        {!! $bodies[$code] !!}
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Bu tilda matn kiritilmagan.') }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- Gallery (shared across languages) --}}
                    @if ($page->images->isNotEmpty())
                        <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                            <h3 class="mb-4 text-sm font-semibold text-gray-800 dark:text-white/90">{{ __('Galereya') }}</h3>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                @foreach ($page->images as $img)
                                    <a href="{{ asset('storage/' . $img->path) }}" target="_blank"
                                       class="group block overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800">
                                        <img src="{{ asset('storage/' . $img->path) }}" alt=""
                                             class="h-32 w-full object-cover transition group-hover:scale-105" />
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Side: cover --}}
                <div class="space-y-6">
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                        @if ($page->cover_image)
                            <img src="{{ asset('storage/' . $page->cover_image) }}" alt="" class="h-48 w-full object-cover" />
                        @else
                            <div class="flex h-48 w-full items-center justify-center bg-gray-100 dark:bg-gray-800">
                                <x-admin.icon name="document-text" class="h-12 w-12 text-gray-300 dark:text-gray-600" />
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
