@props(['item', 'depth' => 0])

@php
    // Recursive desktop navbar item. Top-level (depth 0) sits in the horizontal
    // bar and drops down; nested items live inside a panel and fly out to the
    // right. Loading of children is done once via activeChildrenRecursive.
    $current = url()->current();
    $children = $item->activeChildrenRecursive;
    $hasChildren = $children->isNotEmpty();
    $isCurrent = $item->publicUrl() === $current;
@endphp

@if ($hasChildren)
    <div x-data="{ open: false }" class="relative"
         @mouseenter="open = true" @mouseleave="open = false">
        <button type="button" @click="open = ! open"
            @class([
                'flex items-center gap-1 rounded-lg px-3.5 py-2 text-sm font-medium transition' => $depth === 0,
                'flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm transition' => $depth > 0,
                'text-blue-700' => $depth === 0 && $isCurrent,
                'text-gray-600 hover:bg-gray-50 hover:text-gray-900' => $depth === 0 && ! $isCurrent,
                'text-gray-700 hover:bg-gray-50 hover:text-gray-900' => $depth > 0,
            ])>
            <span>{{ $item->title }}</span>
            @if ($depth === 0)
                {{-- chevron down --}}
                <svg class="h-3.5 w-3.5 transition" :class="open && 'rotate-180'"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                </svg>
            @else
                {{-- chevron right — submenu flies out sideways --}}
                <svg class="h-3.5 w-3.5 flex-none text-gray-400"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6" />
                </svg>
            @endif
        </button>

        <div x-show="open" x-cloak x-transition.origin.top.left
             @class([
                 'absolute z-50 w-64 rounded-xl border border-gray-200 bg-white p-2 shadow-lg',
                 'left-0 top-full mt-1' => $depth === 0,
                 'left-full top-0' => $depth > 0, // adjacent (no gap) so the mouse can cross into it
             ])>
            @foreach ($children as $child)
                <x-site.nav-item :item="$child" :depth="$depth + 1" />
            @endforeach
        </div>
    </div>
@else
    <a href="{{ $item->publicUrl() }}"
       @if ($item->target_blank) target="_blank" rel="noopener noreferrer" @endif
       @class([
           'rounded-lg px-3.5 py-2 text-sm font-medium transition' => $depth === 0,
           'block rounded-lg px-3 py-2 text-sm transition' => $depth > 0,
           'text-blue-700' => $depth === 0 && $isCurrent,
           'text-gray-600 hover:bg-gray-50 hover:text-gray-900' => $depth === 0 && ! $isCurrent,
           'bg-blue-50 font-medium text-blue-700' => $depth > 0 && $isCurrent,
           'text-gray-700 hover:bg-gray-50 hover:text-gray-900' => $depth > 0 && ! $isCurrent,
       ])>{{ $item->title }}</a>
@endif
