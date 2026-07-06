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
    'createTranslatable' => false,   // 3-language (uz/ru/kk) modal
    'createWithParent' => false,     // select parent category (for category)
    'createParents' => [],           // [['id'=>, 'label'=>], ...] — parent selection list
])

@php
    $selectedIds = array_map('strval', old($name, $selected) ?? []);
    $jsOptions = collect($options)->map(fn ($o) => ['id' => (string) $o['id'], 'label' => $o['label']])->values();
    $parentOptions = collect($createParents)->map(fn ($o) => ['id' => (string) $o['id'], 'label' => $o['label']])->values();
    $placeholder ??= __('Tanlang');
    $translatable = (bool) $createTranslatable;
    $withParent = (bool) $createWithParent;
@endphp

<div
    x-data="{
        open: false,
        search: '',
        selected: @js($selectedIds),
        options: @js($jsOptions),
        translatable: {{ $translatable ? 'true' : 'false' }},
        withParent: {{ $withParent ? 'true' : 'false' }},
        parents: @js($parentOptions),
        modalOpen: false, saving: false, err: '',
        form: { uz: '', ru: '', kk: '', single: '', parent_id: '' },
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
        openModal() {
            this.err = '';
            this.form = { uz: '', ru: '', kk: '', single: '', parent_id: '' };
            this.modalOpen = true;
            this.$nextTick(() => this.$refs.first?.focus());
        },
        async create() {
            this.err = '';
            let name;
            if (this.translatable) {
                const uz = this.form.uz.trim(), ru = this.form.ru.trim(), kk = this.form.kk.trim();
                if (!uz || !ru || !kk) { this.err = '{{ __('Barcha tillarni to‘ldiring') }}'; return; }
                name = { uz, ru, kk };
            } else {
                const s = this.form.single.trim();
                if (!s) { this.err = '{{ __('Nomni kiriting') }}'; return; }
                name = s;
            }
            const extra = {};
            if (this.withParent && this.form.parent_id) { extra.parent_id = this.form.parent_id; }
            this.saving = true;
            try {
                const o = await window.lookupCreate('{{ $createType }}', name, extra);
                this.options.push({ id: String(o.id), label: o.name });
                this.selected.push(String(o.id));
                this.modalOpen = false;
            } catch (e) {
                this.err = e.message || '{{ __('Qo‘shishda xatolik') }}';
            }
            this.saving = false;
        },
    }"
    @click.outside="open = false"
    class="relative"
>
    @if ($label || $creatable)
        <div class="mb-1.5 flex items-center justify-between">
            @if ($label)
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">{{ $label }}</label>
            @else
                <span></span>
            @endif
            @if ($creatable)
                <button type="button" @click="openModal()" class="text-theme-xs font-medium text-brand-500 hover:text-brand-600">+ {{ __('Yangi') }}</button>
            @endif
        </div>
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

    {{-- Hidden inputs for submission --}}
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
    </div>

    @error($name)<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
    @error($name . '.*')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
    @if ($help && ! $errors->has($name))<p class="mt-1 text-theme-xs text-gray-400">{{ $help }}</p>@endif

    {{-- Modal --}}
    @if ($creatable)
        <template x-teleport="body">
            <div x-show="modalOpen" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-[2px]" @click="modalOpen = false"></div>
                <div x-show="modalOpen" x-transition
                     class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900"
                     @keydown.escape.window="modalOpen = false">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ $createLabel ?? __('Yangi qo‘shish') }}</h3>
                        <button type="button" @click="modalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
                    </div>

                    <div class="space-y-4">
                        @php
                            $mBase = 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
                        @endphp
                        @if ($translatable)
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Nomi (o‘zbekcha)') }}<span class="text-error-500">*</span></label>
                                <input x-ref="first" x-model="form.uz" @keydown.enter.prevent="create()" :disabled="saving" class="{{ $mBase }}" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Nomi (ruscha)') }}<span class="text-error-500">*</span></label>
                                <input x-model="form.ru" @keydown.enter.prevent="create()" :disabled="saving" class="{{ $mBase }}" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Nomi (qoraqalpoqcha)') }}<span class="text-error-500">*</span></label>
                                <input x-model="form.kk" @keydown.enter.prevent="create()" :disabled="saving" class="{{ $mBase }}" />
                            </div>
                        @else
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Nomi') }}<span class="text-error-500">*</span></label>
                                <input x-ref="first" x-model="form.single" @keydown.enter.prevent="create()" :disabled="saving" class="{{ $mBase }}" />
                            </div>
                        @endif

                        @if ($withParent)
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Ota kategoriya') }}</label>
                                <select x-model="form.parent_id" :disabled="saving" class="{{ $mBase }}">
                                    <option value="">{{ __('Yo‘q (asosiy)') }}</option>
                                    <template x-for="p in parents" :key="p.id">
                                        <option :value="p.id" x-text="p.label"></option>
                                    </template>
                                </select>
                            </div>
                        @endif

                        <p x-show="err" x-cloak x-text="err" class="text-theme-xs text-error-500"></p>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="modalOpen = false" :disabled="saving" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</button>
                        <button type="button" @click="create()" :disabled="saving"
                                class="bg-brand-500 hover:bg-brand-600 rounded-lg px-5 py-2 text-sm font-medium text-white disabled:opacity-60"
                                x-text="saving ? '...' : '{{ __('Qo‘shish') }}'"></button>
                    </div>
                </div>
            </div>
        </template>
    @endif
</div>
