@props([
    'name',
    'label' => null,
    'options' => [],       // [['id' => .., 'label' => ..], ...]
    'xModel',               // outer Alpine path this binds to, e.g. 'form.reader_id'
    'placeholder' => null,
    'required' => false,
    'help' => null,
])

@php
    $jsOptions = collect($options)->map(fn ($o) => ['id' => (string) $o['id'], 'label' => $o['label']])->values();
    $placeholder ??= __('Tanlang');
@endphp

{{-- Single-value select with a live text filter — for pickers with too many
     options to scan by eye (e.g. hundreds of readers). Binds to an OUTER
     Alpine scope's property via the raw `xModel` path (Alpine resolves
     unshadowed expressions up the parent scope chain), not its own state. --}}
<div
    x-data="{
        open: false,
        search: '',
        options: @js($jsOptions),
        labelOf(id) { const o = this.options.find(o => o.id === String(id)); return o ? o.label : ''; },
        get filtered() {
            const s = this.search.toLowerCase();
            return this.options.filter(o => o.label.toLowerCase().includes(s));
        },
        choose(id) { {{ $xModel }} = id; this.open = false; this.search = ''; },
    }"
    @click.outside="open = false"
    class="relative"
>
    @if ($label)
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ $label }}@if ($required)<span class="text-error-500">*</span>@endif</label>
    @endif

    <button type="button" @click="open = !open; if (open) $nextTick(() => $refs.search.focus())"
            class="shadow-theme-xs flex h-11 w-full items-center justify-between rounded-lg border bg-transparent px-4 text-left text-sm text-gray-800 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 {{ $errors->has($name) ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
        <span x-text="{{ $xModel }} ? labelOf({{ $xModel }}) : '{{ $placeholder }}'" :class="! {{ $xModel }} && 'text-gray-400'"></span>
        <span class="text-gray-400">&#9662;</span>
    </button>

    <input type="hidden" name="{{ $name }}" :value="{{ $xModel }}" />

    <div x-show="open" x-cloak
         class="absolute z-40 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-gray-100 p-2 dark:border-gray-800">
            <input type="text" x-model="search" x-ref="search" placeholder="{{ __('Qidirish...') }}"
                   class="h-9 w-full rounded-lg border border-gray-200 bg-transparent px-3 text-sm focus:outline-hidden dark:border-gray-700 dark:text-white/90" />
        </div>
        <div class="max-h-52 overflow-y-auto py-1">
            <template x-for="o in filtered" :key="o.id">
                <button type="button" @click="choose(o.id)"
                        class="flex w-full items-center px-3 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-white/5"
                        :class="{{ $xModel }} === o.id && 'bg-brand-50 text-brand-600 dark:bg-brand-500/10'"
                        x-text="o.label"></button>
            </template>
            <p x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-400">{{ __('Topilmadi') }}</p>
        </div>
    </div>

    @error($name)<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
    @if ($help && ! $errors->has($name))<p class="mt-1 text-theme-xs text-gray-400">{{ $help }}</p>@endif
</div>
