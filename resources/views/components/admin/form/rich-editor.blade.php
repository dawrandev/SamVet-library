@props([
    'name',        // e.g. 'body' -> body[uz], body[ru], body[kk]
    'label' => null,
    'value' => [], // translations array: ['uz'=>'<p>...', 'ru'=>'', 'kk'=>'']
    'help' => null,
])

@php
    $locales = ['uz' => 'O‘zbekcha', 'ru' => 'Русский', 'kk' => 'Qaraqalpaqsha'];
    $translations = is_array($value) ? $value : [];
@endphp

<div data-rich-editor x-data="richEditor" class="space-y-2">
    @if ($label)
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">{{ $label }}</label>
    @endif

    {{-- Language tabs --}}
    <div class="inline-flex gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-gray-800">
        @foreach ($locales as $code => $lbl)
            <button type="button" @click="select('{{ $code }}')"
                    :class="active === '{{ $code }}'
                        ? 'bg-white text-gray-900 shadow-theme-xs dark:bg-gray-700 dark:text-white'
                        : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="rounded-md px-3 py-1.5 text-theme-xs font-medium transition">{{ $lbl }}</button>
        @endforeach
    </div>

    {{-- Panel + textarea for each language (replaced by TinyMCE) --}}
    @foreach ($locales as $code => $lbl)
        <div x-show="active === '{{ $code }}'" @unless($loop->first) x-cloak @endunless>
            {{-- {{ }} escapes; textarea turns entities back into HTML — TinyMCE receives raw HTML, no script runs on the page --}}
            <textarea name="{{ $name }}[{{ $code }}]" x-ref="ta_{{ $code }}"
                      class="js-rich-editor min-h-[460px] w-full rounded-lg border border-gray-300 bg-transparent p-3 text-sm text-gray-800 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 {{ $errors->has($name . '.' . $code) ? 'border-error-500' : '' }}">{{ $translations[$code] ?? '' }}</textarea>
        </div>
    @endforeach

    @error($name . '.uz')<p class="text-theme-xs text-error-500">{{ $message }}</p>@enderror
    @if ($help)<p class="text-theme-xs text-gray-400">{{ $help }}</p>@endif
</div>
