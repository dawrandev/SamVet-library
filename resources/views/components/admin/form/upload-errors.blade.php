{{-- Validation errors returned from an XHR upload (parent x-data="uploadForm"). --}}
<template x-if="uploadErrors && uploadErrors.length">
    <div class="mb-6 rounded-lg border border-error-500/30 bg-error-50 p-4 dark:border-error-500/30 dark:bg-error-500/10">
        <p class="mb-1 text-sm font-semibold text-error-600 dark:text-error-400">{{ __('Faylni yuklab bo‘lmadi:') }}</p>
        <ul class="list-disc space-y-0.5 pl-5">
            <template x-for="msg in uploadErrors" :key="msg">
                <li class="text-theme-sm text-error-600 dark:text-error-400" x-text="msg"></li>
            </template>
        </ul>
    </div>
</template>
