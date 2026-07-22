{{--
    Catalog filter sidebar. Rendered inside the catalog <form> so every control
    submits together via GET. Checkboxes / selects auto-submit for instant
    filtering; free-text fields apply on Enter or the "Filtrlash" button.

    Expects: $filters (CatalogFilters), $categories, $types, $languages, $formats
    (facet collections of {id,label,count}), $yearBounds ({min,max}).
--}}
<div class="rounded-2xl border border-gray-200 bg-white p-5">
    {{-- Heading + clear --}}
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-bold text-gray-900">{{ __('Filtrlar') }}</h2>
        @if ($filters->isActive())
            <a href="{{ route('catalog') }}" class="inline-flex items-center gap-1 text-xs font-medium text-blue-700 hover:text-blue-800">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                {{ __('Tozalash') }}
            </a>
        @endif
    </div>

    {{-- Search --}}
    <div class="mt-5">
        <label for="filter-q" class="text-sm font-semibold text-gray-900">{{ __('Qidiruv') }}</label>
        <div class="relative mt-2">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.34-4.34M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" /></svg>
            <input type="text" id="filter-q" name="q" value="{{ $filters->search }}" placeholder="{{ __('Kitob yoki kalit so‘z...') }}"
                   class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-9 pr-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none" />
        </div>
    </div>

    {{-- Kategoriya: a collapsible tree — each parent's children stay tucked
         under a dropdown toggle (auto-open when one of them is selected),
         since a flat list gets unwieldy once there are many. --}}
    @php
        $categoryTree = collect($categories)->where('parentId', null)->map(fn ($parent) => [
            ...$parent,
            'children' => collect($categories)->where('parentId', $parent['id'])->values(),
        ])->values();
    @endphp

    @if ($categoryTree->isNotEmpty())
        <fieldset class="mt-6 border-t border-gray-100 pt-5">
            <legend class="text-sm font-semibold text-gray-900">{{ __('Kategoriya') }}</legend>
            <div class="mt-3 space-y-1">
                @foreach ($categoryTree as $parent)
                    @php
                        $childIds = $parent['children']->pluck('id')->all();
                        $hasActiveChild = array_intersect($childIds, $filters->categories) !== [];
                    @endphp
                    <div @if ($parent['children']->isNotEmpty()) x-data="{ open: {{ $hasActiveChild ? 'true' : 'false' }} }" @endif>
                        <div class="flex items-center gap-2 py-1">
                            <label class="flex flex-1 cursor-pointer items-center justify-between gap-2 text-sm">
                                <span class="flex items-center gap-2.5 text-gray-600">
                                    <input type="checkbox" name="categories[]" value="{{ $parent['id'] }}" onchange="this.form.submit()"
                                           @checked(in_array($parent['id'], $filters->categories, true))
                                           class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500/40" />
                                    {{ $parent['label'] }}
                                </span>
                                <span class="text-xs tabular-nums text-gray-400">{{ $parent['count'] }}</span>
                            </label>
                            @if ($parent['children']->isNotEmpty())
                                <button type="button" @click="open = !open"
                                        class="flex h-5 w-5 flex-none items-center justify-center text-gray-400 hover:text-gray-600"
                                        aria-label="{{ __('Ochish/yopish') }}">
                                    <svg class="h-3.5 w-3.5 transition-transform" :class="open && 'rotate-180'" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m5 7.5 5 5 5-5" /></svg>
                                </button>
                            @endif
                        </div>
                        @if ($parent['children']->isNotEmpty())
                            <div x-show="open" x-cloak class="ml-5 space-y-1.5 border-l border-gray-100 py-1 pl-3">
                                @foreach ($parent['children'] as $child)
                                    <label class="flex cursor-pointer items-center justify-between gap-2 text-sm">
                                        <span class="flex items-center gap-2.5 text-gray-500">
                                            <input type="checkbox" name="categories[]" value="{{ $child['id'] }}" onchange="this.form.submit()"
                                                   @checked(in_array($child['id'], $filters->categories, true))
                                                   class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500/40" />
                                            {{ $child['label'] }}
                                        </span>
                                        <span class="text-xs tabular-nums text-gray-400">{{ $child['count'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </fieldset>
    @endif

    @php
        // Reusable facet groups: [legend, request key, facet collection, selected ids].
        $groups = [
            [__('Shakli'), 'formats', $formats, $filters->formats],
            [__('Turi'), 'types', $types, $filters->types],
            [__('Tili'), 'languages', $languages, $filters->languages],
        ];
    @endphp

    @foreach ($groups as [$legend, $key, $facets, $selected])
        @if ($facets->isNotEmpty())
            <fieldset class="mt-6 border-t border-gray-100 pt-5">
                <legend class="text-sm font-semibold text-gray-900">{{ $legend }}</legend>
                <div class="mt-3 space-y-2.5">
                    @foreach ($facets as $facet)
                        <label class="flex cursor-pointer items-center justify-between gap-2 text-sm">
                            <span class="flex items-center gap-2.5 text-gray-600">
                                <input type="checkbox" name="{{ $key }}[]" value="{{ $facet['id'] }}" onchange="this.form.submit()"
                                       @checked(in_array($facet['id'], $selected, true))
                                       class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500/40" />
                                {{ $facet['label'] }}
                            </span>
                            <span class="text-xs tabular-nums text-gray-400">{{ $facet['count'] }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endif
    @endforeach

    {{-- Publication year range --}}
    <fieldset class="mt-6 border-t border-gray-100 pt-5">
        <legend class="text-sm font-semibold text-gray-900">{{ __('Nashr yili') }}</legend>
        <div class="mt-3 grid grid-cols-2 gap-3">
            <input type="number" name="year_from" value="{{ $filters->yearFrom }}" inputmode="numeric"
                   min="{{ $yearBounds['min'] ?? 1900 }}" max="{{ $yearBounds['max'] ?? 2100 }}"
                   placeholder="{{ __('Dan') }} {{ $yearBounds['min'] }}" onchange="this.form.submit()"
                   class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none" />
            <input type="number" name="year_to" value="{{ $filters->yearTo }}" inputmode="numeric"
                   min="{{ $yearBounds['min'] ?? 1900 }}" max="{{ $yearBounds['max'] ?? 2100 }}"
                   placeholder="{{ __('Gacha') }} {{ $yearBounds['max'] }}" onchange="this.form.submit()"
                   class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none" />
        </div>
    </fieldset>

    {{-- Author --}}
    <div class="mt-6 border-t border-gray-100 pt-5">
        <label for="filter-author" class="text-sm font-semibold text-gray-900">{{ __('Muallif') }}</label>
        <input type="text" id="filter-author" name="author" value="{{ $filters->author }}" placeholder="{{ __('Muallif ismi...') }}"
               class="mt-3 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none" />
    </div>

    <button type="submit" class="mt-6 w-full rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">
        {{ __('Filtrlash') }}
    </button>
</div>
