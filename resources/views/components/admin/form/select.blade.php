@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'required' => false,
    'placeholder' => null,
    'help' => null,
    'creatable' => false,   // "add on the fly" (modal)
    'createType' => null,    // LookupService type (e.g. 'book_type')
    'createLabel' => null,   // modal title / single-language input label
    'createTranslatable' => false, // 3-language (uz/ru/kk) modal

    // --- Language-aware labelling (used by the book form) ---
    'localeMap' => [],       // [option id => 'uz'|'ru'|'kk'] — this select drives the shared locale
    'translations' => [],    // [option id => ['uz'=>..,'ru'=>..,'kk'=>..]] — label options in that locale
    'awaitLocale' => false,  // stay disabled until a language has been picked
])

@php
    $base = 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90';
    $border = $errors->has($name) ? 'border-error-500' : 'border-gray-300 dark:border-gray-700';
    $current = old($name, $selected);
@endphp

@if (! $creatable)
    {{-- Simple select --}}
    <div>
        @if ($label)
            <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                {{ $label }}@if ($required)<span class="text-error-500">*</span>@endif
            </label>
        @endif
        <select name="{{ $name }}" id="{{ $name }}" @required($required) {{ $attributes->merge(['class' => "$base $border"]) }}>
            @if ($placeholder)<option value="">{{ $placeholder }}</option>@endif
            @foreach ($options as $option)
                <option value="{{ $option->id }}" @selected((string) $current === (string) $option->id)>{{ $option->name }}</option>
            @endforeach
        </select>
        @error($name)<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
        @if ($help && ! $errors->has($name))<p class="mt-1 text-theme-xs text-gray-400">{{ $help }}</p>@endif
    </div>
@else
    {{-- With modal-based creation (Alpine) --}}
    @php
        $optionsArray = collect($options)->map(fn ($o) => ['id' => (string) $o->id, 'name' => $o->name])->values();
        $translatable = (bool) $createTranslatable;
        $isLocaleSource = ! empty($localeMap);
    @endphp
    <div x-data="{
            options: @js($optionsArray),
            selected: @js((string) $current),
            translatable: {{ $translatable ? 'true' : 'false' }},
            localeMap: @js((object) $localeMap),
            labels: @js((object) $translations),
            isLocaleSource: {{ $isLocaleSource ? 'true' : 'false' }},
            awaitLocale: {{ $awaitLocale ? 'true' : 'false' }},
            open: false, saving: false, err: '',
            form: { uz: '', ru: '', kk: '', single: '' },
            init() {
                // x-for paints the options after x-model binds, so the initial
                // value has to be re-applied once they exist — otherwise editing
                // a record silently clears the select on save.
                this.$nextTick(() => { this.$refs.select.value = this.selected; });

                if (! this.isLocaleSource) return;
                this.publishLocale();
                this.$watch('selected', () => this.publishLocale());
            },
            /** Share the chosen language's locale; an unmapped language falls back to uz. */
            publishLocale() {
                this.$store.lookupLocale.value = this.selected
                    ? (this.localeMap[this.selected] || 'uz')
                    : null;
            },
            /** Option label in the shared locale, falling back to the stored name. */
            label(o) {
                const locale = this.$store.lookupLocale.value;
                const translated = locale && this.labels[o.id] ? this.labels[o.id][locale] : null;
                return translated || o.name;
            },
            get locked() {
                return this.awaitLocale && ! this.$store.lookupLocale.value;
            },
            /** Sort by what the user actually reads (only when labels are translated). */
            get visibleOptions() {
                if (! Object.keys(this.labels).length) return this.options;
                return [...this.options].sort((a, b) => this.label(a).localeCompare(this.label(b)));
            },
            openModal() {
                this.err = '';
                this.form = { uz: '', ru: '', kk: '', single: '' };
                this.open = true;
                this.$nextTick(() => this.$refs.first?.focus());
            },
            async save() {
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
                this.saving = true;
                try {
                    const o = await window.lookupCreate('{{ $createType }}', name);
                    this.options.push({ id: String(o.id), name: o.name });
                    if (this.translatable) this.labels[String(o.id)] = name;
                    this.selected = String(o.id);
                    this.open = false;
                } catch (e) {
                    this.err = e.message || '{{ __('Qo‘shishda xatolik') }}';
                }
                this.saving = false;
            }
         }">
        <div class="mb-1.5 flex items-center justify-between">
            @if ($label)
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">{{ $label }}@if ($required)<span class="text-error-500">*</span>@endif</label>
            @endif
            <button type="button" @click="openModal()" :disabled="locked"
                    class="text-theme-xs font-medium text-brand-500 hover:text-brand-600 disabled:cursor-not-allowed disabled:opacity-40">+ {{ __('Yangi') }}</button>
        </div>

        <select name="{{ $name }}" x-ref="select" x-model="selected" :disabled="locked" @required($required)
                :class="locked && 'cursor-not-allowed opacity-60'" class="{{ $base }} {{ $border }}">
            @if ($placeholder)<option value="">{{ $placeholder }}</option>@endif
            <template x-for="o in visibleOptions" :key="o.id">
                <option :value="o.id" x-text="label(o)"></option>
            </template>
        </select>

        @error($name)<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
        @if ($awaitLocale)
            <p x-show="locked" x-cloak class="mt-1 text-theme-xs text-gray-400">{{ __('Avval tilni tanlang.') }}</p>
        @endif
        @if ($help && ! $errors->has($name))<p class="mt-1 text-theme-xs text-gray-400">{{ $help }}</p>@endif

        {{-- Modal --}}
        <template x-teleport="body">
            <div x-show="open" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-[2px]" @click="open = false"></div>
                <div x-show="open" x-transition
                     class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900"
                     @keydown.escape.window="open = false">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ $createLabel ?? __('Yangi qo‘shish') }}</h3>
                        <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
                    </div>

                    <div class="space-y-4">
                        @if ($translatable)
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Nomi (o‘zbekcha)') }}<span class="text-error-500">*</span></label>
                                <input x-ref="first" x-model="form.uz" @keydown.enter.prevent="save()" :disabled="saving" class="{{ $base }} border-gray-300 dark:border-gray-700" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Nomi (ruscha)') }}<span class="text-error-500">*</span></label>
                                <input x-model="form.ru" @keydown.enter.prevent="save()" :disabled="saving" class="{{ $base }} border-gray-300 dark:border-gray-700" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Nomi (qoraqalpoqcha)') }}<span class="text-error-500">*</span></label>
                                <input x-model="form.kk" @keydown.enter.prevent="save()" :disabled="saving" class="{{ $base }} border-gray-300 dark:border-gray-700" />
                            </div>
                        @else
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Nomi') }}<span class="text-error-500">*</span></label>
                                <input x-ref="first" x-model="form.single" @keydown.enter.prevent="save()" :disabled="saving" class="{{ $base }} border-gray-300 dark:border-gray-700" />
                            </div>
                        @endif

                        <p x-show="err" x-cloak x-text="err" class="text-theme-xs text-error-500"></p>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="open = false" :disabled="saving" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</button>
                        <button type="button" @click="save()" :disabled="saving"
                                class="bg-brand-500 hover:bg-brand-600 rounded-lg px-5 py-2 text-sm font-medium text-white disabled:opacity-60"
                                x-text="saving ? '...' : '{{ __('Qo‘shish') }}'"></button>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endif
