@props(['item', 'depth' => 0])

@php
    // Recursive mobile navbar item: a nested accordion. Each level with children
    // toggles them open/closed; leaves are plain links, indented by an inset border.
    $current = url()->current();
    $children = $item->activeChildrenRecursive;
    $hasChildren = $children->isNotEmpty();
    $isCurrent = $item->publicUrl() === $current;
@endphp

@if ($hasChildren)
    <div x-data="{ sub: false }">
        <button type="button" @click="sub = ! sub"
                class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-left text-sm font-medium text-gray-700 hover:bg-gray-50">
            <span>{{ $item->title }}</span>
            <svg class="h-4 w-4 transition" :class="sub && 'rotate-180'"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
            </svg>
        </button>
        <div x-show="sub" x-cloak class="ml-3 mt-0.5 flex flex-col gap-0.5 border-l border-gray-100 pl-3">
            @foreach ($children as $child)
                <x-site.nav-item-mobile :item="$child" :depth="$depth + 1" />
            @endforeach
        </div>
    </div>
@else
    <a href="{{ $item->publicUrl() }}"
       @if ($item->target_blank) target="_blank" rel="noopener noreferrer" @endif
       @class([
           'rounded-lg px-3 py-2.5 text-sm font-medium',
           'bg-blue-50 text-blue-700' => $isCurrent,
           'text-gray-600 hover:bg-gray-50' => ! $isCurrent && $depth > 0,
           'text-gray-700 hover:bg-gray-50' => ! $isCurrent && $depth === 0,
       ])>{{ $item->title }}</a>
@endif
