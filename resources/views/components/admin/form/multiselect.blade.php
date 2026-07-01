@props([
    'name',
    'label' => null,
    'options' => [],       // [['id' => .., 'label' => ..], ...]
    'selected' => [],
    'placeholder' => null,
    'help' => null,
    'creatable' => false,
    'createType' => null,
    'createLabel' => null,
])

@php
    $selectedIds = array_map('strval', old($name, $selected) ?? []);
    $jsOptions = collect($options)->map(fn ($o) => ['id' => (string) $o['id'], 'label' => $o['label']])->values();
    $placeholder ??= __('Tanlang');
@endphp

<div
    x-data="{
        open: false,
        search: '',
        selected: @js($selectedIds),
        options: @js($jsOptions),
        newName: '', saving: false, err: '',
        toggle(id) {
            this.selected.includes(id)
                ? this.selected = this.selected.filter(s => s !== id)
                : this.selected.push(id);
        },
        labelOf(id) { const o = this.options.find(o => o.id === id); return o ? o.label : ''; },
        get filtered() {
            const s = this.search.toLowerCase();
            return this.options.filter(o => o.label.toLowerCase().includes(s));
        },
        async create() {
            const n = this.newName.trim();
            if (!n) return;
            this.saving = true; this.err = '';
            try {
                const o = await window.lookupCreate('{{ $createType }}', n);
                this.options.push({ id: String(o.id), label: o.name });
                this.selected.push(String(o.id));
                this.newName = '';
            } catch (e) {
                this.err = '{{ __('Qo‘shishda xatolik') }}';
            }
            this.saving = false;
        },
    }"
    @click.outside="open = false"
    class="relative"
>
    @if ($label)
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ $label }}</label>
    @endif

    <button type="button" @click="open = !open"
        class="shadow-theme-xs flex min-h-11 w-full flex-wrap items-center gap-1.5 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-left text-sm dark:border-gray-700 dark:bg-gray-900">
        <span x-show="selected.length === 0" class="text-gray-400">{{ $placeholder }}</span>
        <template x-for="id in selected" :key="id">
            <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-0.5 text-theme-xs font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                <span x-text="labelOf(id)"></span>
                <span @click.stop="toggle(id)" class="cursor-pointer hover:text-brand-800">&times;</span>
            </span>
        </template>
        <span class="ml-auto text-gray-400">&#9662;</span>
    </button>

    {{-- Yuborish uchun yashirin inputlar --}}
    <template x-for="id in selected" :key="'i-' + id">
        <input type="hidden" name="{{ $name }}[]" :value="id" />
    </template>

    {{-- Dropdown --}}
    <div x-show="open" x-cloak
        class="absolute z-40 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-gray-100 p-2 dark:border-gray-800">
            <input type="text" x-model="search" placeholder="{{ __('Qidirish...') }}"
                class="h-9 w-full rounded-lg border border-gray-200 bg-transparent px-3 text-sm focus:outline-hidden dark:border-gray-700 dark:text-white/90" />
        </div>
        <div class="max-h-52 overflow-y-auto py-1">
            <template x-for="o in filtered" :key="o.id">
                <label class="flex cursor-pointer items-center gap-2.5 px-3 py-2 hover:bg-gray-50 dark:hover:bg-white/5">
                    <input type="checkbox" :checked="selected.includes(o.id)" @change="toggle(o.id)"
                        class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/20 dark:border-gray-700" />
                    <span class="text-sm text-gray-700 dark:text-gray-300" x-text="o.label"></span>
                </label>
            </template>
            <p x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-400">{{ __('Topilmadi') }}</p>
        </div>

        @if ($creatable)
            {{-- Inline qo'shish --}}
            <div class="border-t border-gray-100 p-2 dark:border-gray-800">
                <div class="flex gap-2">
                    <input type="text" x-model="newName" @keydown.enter.prevent="create()" :disabled="saving"
                        placeholder="{{ $createLabel ?? __('Yangi qo‘shish...') }}"
                        class="h-9 flex-1 rounded-lg border border-gray-200 bg-transparent px-3 text-sm focus:outline-hidden dark:border-gray-700 dark:text-white/90" />
                    <button type="button" @click="create()" :disabled="saving"
                        class="bg-brand-500 hover:bg-brand-600 h-9 flex-shrink-0 rounded-lg px-3 text-theme-xs font-medium text-white disabled:opacity-60"
                        x-text="saving ? '...' : '{{ __('Qo‘shish') }}'"></button>
                </div>
                <p x-show="err" x-cloak x-text="err" class="mt-1 text-theme-xs text-error-500"></p>
            </div>
        @endif
    </div>

    @error($name)<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
    @error($name . '.*')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
    @if ($help && ! $errors->has($name))<p class="mt-1 text-theme-xs text-gray-400">{{ $help }}</p>@endif
</div>
