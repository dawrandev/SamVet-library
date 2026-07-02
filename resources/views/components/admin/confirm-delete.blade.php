{{-- O'chirishni tasdiqlash — umumiy modal ($store.confirm orqali chaqiriladi) --}}
<div x-cloak x-show="$store.confirm.open" class="fixed inset-0 z-999999 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-[2px]" @click="$store.confirm.close()"></div>

    <div x-show="$store.confirm.open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         @keydown.escape.window="$store.confirm.close()"
         class="relative w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl dark:bg-gray-900">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-error-50 dark:bg-error-500/15">
            <svg class="text-error-500" width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 3h6M4 7h16M6 7l1 13a2 2 0 002 2h6a2 2 0 002-2l1-13M10 11v6M14 11v6"
                      stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </div>

        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('O‘chirishni tasdiqlang') }}</h3>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400"
           x-text="$store.confirm.message || '{{ __('Bu amalni ortga qaytarib bo‘lmaydi.') }}'"></p>

        <form :action="$store.confirm.action" method="POST" class="mt-6 flex gap-3">
            @csrf
            @method('DELETE')
            <button type="button" @click="$store.confirm.close()"
                    class="flex-1 rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800">
                {{ __('Bekor qilish') }}
            </button>
            <button type="submit"
                    class="flex-1 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-700">
                {{ __('O‘chirish') }}
            </button>
        </form>
    </div>
</div>
