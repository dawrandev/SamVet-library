@props([
    'conditions' => [],   // CopyCondition[] — the full set of pickable condition tags
])

{{--
    Shared "record a material as returned" modal — used by both the reader
    detail page and the Loans (Berilgan kitoblar) admin section, so the two
    screens can never drift apart again (they used to: one had a condition
    picker, the other didn't ask for a condition at all).

    Expects the ENCLOSING Alpine scope to already provide:
      - returnOpen: boolean       (whether this modal is shown)
      - returnUrl: string         (the loans.return route for the target loan)
      - returnConditions: array   (checked condition values, e.g. ['old', 'torn'])
      - a way to open it, conventionally openReturn(url, conditions)
--}}
<template x-teleport="body">
    <div x-show="returnOpen" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-gray-900/50" @click="returnOpen = false"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900" @keydown.escape.window="returnOpen = false">
            <h4 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Materialni qaytarish') }}</h4>

            <form method="POST" :action="returnUrl" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Qaytarilgandagi holati') }}</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($conditions as $condition)
                            <label
                                :class="returnConditions.includes('{{ $condition->value }}')
                                    ? 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400'
                                    : 'border-gray-200 text-gray-600 dark:border-gray-700 dark:text-gray-400'"
                                class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border px-3 py-1.5 text-theme-xs font-medium transition">
                                <input type="checkbox" name="returned_condition[]" value="{{ $condition->value }}"
                                       x-model="returnConditions" class="sr-only" />
                                {{ $condition->label() }}
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1.5 text-theme-xs text-gray-400">{{ __('Berilgandagi holati(lar) taklif sifatida belgilangan — kerak bo‘lsa o‘zgartiring.') }}</p>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="returnOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</button>
                    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">{{ __('Tasdiqlash') }}</button>
                </div>
            </form>
        </div>
    </div>
</template>
