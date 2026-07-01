@props([
    'title',
    'count' => 0,
    'addLabel' => null,
])

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ $title }}</h2>
        <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">{{ __('Jami') }}: {{ $count }}</p>
    </div>
    <button type="button" @click="openCreate()"
            class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
        <span class="text-lg leading-none">+</span> {{ $addLabel ?? __('Qo‘shish') }}
    </button>
</div>
