@props([
    'name',            // masalan 'title' -> title[uz], title[ru], title[kk]
    'label' => null,
    'value' => [],     // ['uz'=>'', 'ru'=>'', 'kk'=>'']
    'placeholders' => [],
    'textarea' => false,
    'rows' => 2,
    'required' => false,
    'help' => null,
])

@php
    $locales = ['uz' => 'UZ', 'ru' => 'RU', 'kk' => 'KK'];
    $tr = is_array($value) ? $value : [];
    $base = 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90';
    $border = $errors->has($name . '.uz') || $errors->has($name . '.ru') || $errors->has($name . '.kk') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700';
@endphp

<div x-data="{ loc: 'uz' }" class="space-y-1.5">
    <div class="flex items-center justify-between gap-3">
        @if ($label)
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">{{ $label }}@if ($required)<span class="text-error-500">*</span>@endif</label>
        @else
            <span></span>
        @endif
        {{-- Til tablari (o'ngda, ixcham) --}}
        <div class="inline-flex gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-gray-800">
            @foreach ($locales as $code => $lbl)
                <button type="button" @click="loc = '{{ $code }}'"
                        :class="loc === '{{ $code }}' ? 'bg-white text-gray-900 shadow-theme-xs dark:bg-gray-700 dark:text-white' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                        class="rounded-md px-2.5 py-1 text-[11px] font-semibold transition">{{ $lbl }}</button>
            @endforeach
        </div>
    </div>

    @foreach ($locales as $code => $lbl)
        <div x-show="loc === '{{ $code }}'" @unless($loop->first) x-cloak @endunless>
            @if ($textarea)
                <textarea name="{{ $name }}[{{ $code }}]" rows="{{ $rows }}" placeholder="{{ $placeholders[$code] ?? '' }}"
                          class="{{ $base }} {{ $border }} py-2.5">{{ $tr[$code] ?? '' }}</textarea>
            @else
                <input type="text" name="{{ $name }}[{{ $code }}]" value="{{ $tr[$code] ?? '' }}" placeholder="{{ $placeholders[$code] ?? '' }}"
                       class="{{ $base }} {{ $border }} h-11" />
            @endif
        </div>
    @endforeach

    @error($name . '.uz')<p class="text-theme-xs text-error-500">{{ $message }}</p>@enderror
    @if ($help)<p class="text-theme-xs text-gray-400">{{ $help }}</p>@endif
</div>
