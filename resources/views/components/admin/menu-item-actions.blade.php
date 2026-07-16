@props(['node'])

{{-- Menu row actions: add child / edit / delete --}}
<div class="flex flex-none items-center gap-1">
    <a href="{{ route('admin.menu-items.create', ['parent' => $node->id]) }}"
       class="text-theme-xs inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 font-medium text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
       title="{{ __('Ichki menyu qo‘shish') }}">
        <span class="text-sm leading-none">+</span> {{ __('Bola') }}
    </a>
    @if ($node->type === \App\Enums\MenuItemType::Page)
        <a href="{{ route('admin.menu-items.page.show', $node) }}"
           class="text-theme-xs inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 font-medium text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
           title="{{ __('Sahifani ko‘rish') }}">
            <x-admin.icon name="eye" class="h-3.5 w-3.5" /> {{ __('Ko‘rish') }}
        </a>
        <a href="{{ route('admin.menu-items.page.edit', $node) }}"
           class="text-theme-xs inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 font-medium text-brand-500 transition hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
           title="{{ __('Sahifa matnini tahrirlash') }}">
            <x-admin.icon name="document-text" class="h-3.5 w-3.5" /> {{ __('Sahifa') }}
        </a>
    @endif
    <a href="{{ route('admin.menu-items.edit', $node) }}"
       class="text-theme-xs rounded-lg px-2.5 py-1.5 font-medium text-brand-500 transition hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
       title="{{ __('Tahrirlash') }}">{{ __('Tahrirlash') }}</a>
    <button type="button"
            @click="$store.confirm.ask(@js(route('admin.menu-items.destroy', $node)), @js(__('«:title» va uning barcha ichki menyulari o‘chiriladi.', ['title' => $node->getTranslation('title', 'uz')])))"
            class="text-theme-xs rounded-lg px-2.5 py-1.5 font-medium text-error-500 transition hover:bg-error-50 hover:text-error-600 dark:hover:bg-error-500/10"
            title="{{ __('O‘chirish') }}">{{ __('O‘chirish') }}</button>
</div>
