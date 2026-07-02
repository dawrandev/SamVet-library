@props([
    'name',
    'label' => null,
    'value' => [],       // ['uz'=>.., 'ru'=>.., 'kk'=>..] (tahrirda getTranslations)
    'help' => null,
    'placeholder' => '',     // umumiy (fallback)
    'placeholders' => [],    // ['uz'=>.., 'ru'=>.., 'kk'=>..] — har til uchun alohida
])

@php
    $value = is_array($value) ? $value : [];
    $placeholders = is_array($placeholders) ? $placeholders : [];
    $langs = ['uz' => __('O‘zbekcha'), 'ru' => __('Ruscha'), 'kk' => __('Qoraqalpoqcha')];
    $base = 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
@endphp

<div>
    @if ($label)
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ $label }}</label>
    @endif

    <div class="space-y-2">
        @foreach ($langs as $code => $langLabel)
            <div class="flex items-center gap-2">
                <span class="w-9 flex-shrink-0 text-center text-theme-xs font-medium uppercase text-gray-400">{{ $code }}</span>
                <input type="text" name="{{ $name }}[{{ $code }}]"
                    value="{{ old("$name.$code", $value[$code] ?? '') }}"
                    placeholder="{{ $placeholders[$code] ?? $placeholder }}"
                    class="{{ $base }} @error("$name.$code") border-error-500 @enderror" />
            </div>
            @error("$name.$code")<p class="mt-1 pl-11 text-theme-xs text-error-500">{{ $message }}</p>@enderror
        @endforeach
    </div>

    @if ($help)
        <p class="mt-1 text-theme-xs text-gray-400">{{ $help }}</p>
    @endif
</div>
