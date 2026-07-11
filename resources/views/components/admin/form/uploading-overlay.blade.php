{{-- Blocks the page while a large file uploads (parent x-data="uploadForm"). --}}
<template x-teleport="body">
    <div x-show="uploading" x-cloak
         class="fixed inset-0 z-99999 flex items-center justify-center bg-gray-900/60 backdrop-blur-[2px]">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl dark:bg-gray-900">
            <svg class="mx-auto h-8 w-8 animate-spin text-brand-500" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4z"></path>
            </svg>

            <p class="mt-4 text-base font-semibold text-gray-800 dark:text-white/90"
               x-text="processing ? '{{ __('Serverda saqlanmoqda...') }}' : '{{ __('Fayl yuklanmoqda...') }}'"></p>

            <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                <div class="h-full rounded-full bg-brand-500 transition-all duration-150"
                     :class="processing && 'animate-pulse'" :style="`width: ${progress}%`"></div>
            </div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400"
               x-text="progress + '%' + (progressText ? ' · ' + progressText : '')"></p>

            <p class="mt-4 text-theme-xs text-gray-400">{{ __('Katta fayl yuklanishi biroz vaqt oladi. Iltimos, sahifani yopmang.') }}</p>
        </div>
    </div>
</template>
