@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'required' => false,
    'placeholder' => null,
    'help' => null,
    'creatable' => false,   // inline "shu zahoti qo'shish"
    'createType' => null,    // LookupService turi (masalan 'book_type')
    'createLabel' => null,   // qo'shish inputining placeholder'i
])

@php
    $base = 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90';
    $border = $errors->has($name) ? 'border-error-500' : 'border-gray-300 dark:border-gray-700';
    $current = old($name, $selected);
@endphp

@if (! $creatable)
    {{-- Oddiy select --}}
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
    {{-- Inline qo'shish bilan (Alpine) --}}
    @php
        $optionsArray = collect($options)->map(fn ($o) => ['id' => (string) $o->id, 'name' => $o->name])->values();
    @endphp
    <div x-data="{
            options: @js($optionsArray),
            selected: @js((string) $current),
            adding: false, newName: '', saving: false, err: '',
            async save() {
                const n = this.newName.trim();
                if (!n) return;
                this.saving = true; this.err = '';
                try {
                    const o = await window.lookupCreate('{{ $createType }}', n);
                    this.options.push({ id: String(o.id), name: o.name });
                    this.selected = String(o.id);
                    this.newName = ''; this.adding = false;
                } catch (e) {
                    this.err = '{{ __('Qo‘shishda xatolik') }}';
                }
                this.saving = false;
            }
         }">
        <div class="mb-1.5 flex items-center justify-between">
            @if ($label)
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">{{ $label }}@if ($required)<span class="text-error-500">*</span>@endif</label>
            @endif
            <button type="button" @click="adding = !adding; $nextTick(() => adding && $refs.newInput?.focus())"
                    class="text-theme-xs font-medium text-brand-500 hover:text-brand-600">+ {{ __('Yangi') }}</button>
        </div>

        <select name="{{ $name }}" x-model="selected" @required($required) class="{{ $base }} {{ $border }}">
            @if ($placeholder)<option value="">{{ $placeholder }}</option>@endif
            <template x-for="o in options" :key="o.id">
                <option :value="o.id" x-text="o.name"></option>
            </template>
        </select>

        {{-- Inline qo'shish qatori --}}
        <div x-show="adding" x-cloak class="mt-2 flex gap-2">
            <input x-ref="newInput" x-model="newName" @keydown.enter.prevent="save()" :disabled="saving"
                   placeholder="{{ $createLabel ?? __('Yangi nom') }}"
                   class="{{ $base }} border-gray-300 dark:border-gray-700" />
            <button type="button" @click="save()" :disabled="saving"
                    class="bg-brand-500 hover:bg-brand-600 h-11 flex-shrink-0 rounded-lg px-4 text-sm font-medium text-white disabled:opacity-60"
                    x-text="saving ? '...' : '{{ __('Qo‘shish') }}'"></button>
        </div>
        <p x-show="err" x-cloak x-text="err" class="mt-1 text-theme-xs text-error-500"></p>

        @error($name)<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
        @if ($help && ! $errors->has($name))<p class="mt-1 text-theme-xs text-gray-400">{{ $help }}</p>@endif
    </div>
@endif
