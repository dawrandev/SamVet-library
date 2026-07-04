{{-- Rekursiv menyu tuguni (ildiz ostidagi bolalar). $node = MenuItem --}}
{{-- Ulanish chizig'i: chapda vertikal (border-l), har qatorda kichik gorizontal tirnoq (span). --}}
<div class="relative border-l border-gray-200 pl-5 dark:border-gray-800">
    <div class="group relative flex items-center justify-between gap-3 rounded-lg py-1.5 pl-3 pr-2 transition hover:bg-gray-50 dark:hover:bg-white/[0.03]">
        {{-- gorizontal ulanish tirnog'i --}}
        <span class="absolute -left-5 top-1/2 h-px w-4 -translate-y-1/2 bg-gray-200 dark:bg-gray-800"></span>

        <div class="flex min-w-0 items-center gap-2">
            <span class="h-1.5 w-1.5 flex-none rounded-full bg-gray-300 dark:bg-gray-600"></span>
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <span class="truncate text-sm font-medium text-gray-700 dark:text-gray-200">{{ $node->getTranslation('title', 'uz') }}</span>
                    @unless ($node->is_active)
                        <span class="text-theme-xs rounded-full bg-gray-100 px-2 py-0.5 font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ __('Nofaol') }}</span>
                    @endunless
                    @if ($node->target_blank)
                        <span class="text-gray-400" title="{{ __('Yangi oynada ochiladi') }}">&#8599;</span>
                    @endif
                    @if ($node->children->isNotEmpty())
                        <span class="text-theme-xs text-gray-400">{{ $node->children->count() }} {{ __('ta') }}</span>
                    @endif
                </div>
                @if ($node->url)
                    <span class="text-theme-xs truncate text-gray-400">{{ $node->url }}</span>
                @endif
            </div>
        </div>

        <div class="opacity-100 sm:opacity-0 sm:transition sm:group-hover:opacity-100">
            <x-admin.menu-item-actions :node="$node" />
        </div>
    </div>

    {{-- Nevaralari (rekursiv) --}}
    @foreach ($node->children as $child)
        @include('pages.admin.menu-items.partials.tree-node', ['node' => $child])
    @endforeach
</div>
