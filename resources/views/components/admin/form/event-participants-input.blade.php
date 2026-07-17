@props([
    'readers',      // Collection<Reader> — id, full_name
    'roles',        // App\Enums\EventRole::cases()
    'value' => [],  // [['is_external'=>bool,'reader_id'=>?int,'reader_label'=>?string,'external_name'=>?string,'role'=>string], ...]
    'label' => null,
    'help' => null,
])

@php
    $base = 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 border-gray-300 dark:border-gray-700';
    $readerOptions = collect($readers)->map(fn ($r) => ['id' => (string) $r->id, 'label' => $r->full_name])->values();
    $initialRows = collect($value)
        ->map(fn ($p) => [
            'is_external' => (bool) ($p['is_external'] ?? false),
            'reader_id' => (string) ($p['reader_id'] ?? ''),
            'reader_label' => $p['reader_label'] ?? '',
            'external_name' => $p['external_name'] ?? '',
            'role' => (string) ($p['role'] ?? ''),
        ])
        ->values();
@endphp

{{-- Each row is either a registered reader (searched from the full reader list,
     same searchable-dropdown idiom as searchable-select.blade.php, hand-inlined
     here since a Blade component can't be nested inside an Alpine x-for template)
     or a free-typed outside guest. --}}
<div x-data="{
        readerOptions: @js($readerOptions),
        rows: @js($initialRows),
        addRow(external) { this.rows.push({ is_external: external, reader_id: '', reader_label: '', external_name: '', role: '' }); },
        removeRow(i) { this.rows.splice(i, 1); },
    }"
>
    @if ($label)
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ $label }}</label>
    @endif

    <template x-for="(row, i) in rows" :key="i">
        <div class="mb-3 rounded-lg border border-gray-200 p-3 dark:border-gray-800"
             x-data="{ open: false, search: '' }" @click.outside="open = false">
            <div class="mb-2 flex items-center gap-4 text-sm text-gray-700 dark:text-gray-300">
                <label class="flex items-center gap-1.5">
                    <input type="radio" :name="`ptype_${i}`" :checked="!row.is_external"
                           @change="row.is_external = false; row.external_name = ''" class="text-brand-500" />
                    {{ __('O‘quvchi') }}
                </label>
                <label class="flex items-center gap-1.5">
                    <input type="radio" :name="`ptype_${i}`" :checked="row.is_external"
                           @change="row.is_external = true; row.reader_id = ''; row.reader_label = ''" class="text-brand-500" />
                    {{ __('Tashqi ishtirokchi') }}
                </label>
                <button type="button" @click="removeRow(i)"
                        class="ml-auto text-gray-400 hover:text-error-500">&times;</button>
            </div>

            <input type="hidden" :name="`participants[${i}][is_external]`" :value="row.is_external ? 1 : 0" />

            <template x-if="!row.is_external">
                <div class="relative mb-2">
                    <button type="button" @click="open = !open"
                            class="{{ $base }} flex items-center justify-between text-left">
                        <span x-text="row.reader_label || '{{ __('O‘quvchini tanlang') }}'" :class="!row.reader_id && 'text-gray-400'"></span>
                        <span class="text-gray-400">&#9662;</span>
                    </button>
                    <input type="hidden" :name="`participants[${i}][reader_id]`" :value="row.reader_id" />
                    <div x-show="open" x-cloak
                         class="absolute z-40 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-800 dark:bg-gray-900">
                        <div class="border-b border-gray-100 p-2 dark:border-gray-800">
                            <input type="text" x-model="search" placeholder="{{ __('Qidirish...') }}"
                                   class="h-9 w-full rounded-lg border border-gray-200 bg-transparent px-3 text-sm focus:outline-hidden dark:border-gray-700 dark:text-white/90" />
                        </div>
                        <div class="max-h-52 overflow-y-auto py-1">
                            <template x-for="o in readerOptions.filter(o => o.label.toLowerCase().includes(search.toLowerCase()))" :key="o.id">
                                <button type="button" @click="row.reader_id = o.id; row.reader_label = o.label; open = false; search = ''"
                                        class="flex w-full items-center px-3 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-white/5" x-text="o.label"></button>
                            </template>
                            <p x-show="readerOptions.filter(o => o.label.toLowerCase().includes(search.toLowerCase())).length === 0"
                               class="px-3 py-2 text-sm text-gray-400">{{ __('Topilmadi') }}</p>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="row.is_external">
                <input type="text" :name="`participants[${i}][external_name]`" x-model="row.external_name"
                       placeholder="{{ __('Ism sharifi') }}" autocomplete="off" class="{{ $base }} mb-2" />
            </template>

            <select :name="`participants[${i}][role]`" x-model="row.role" class="{{ $base }}">
                <option value="">{{ __('Ishtirok maqsadini tanlang') }}</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}">{{ $role->label() }}</option>
                @endforeach
            </select>
        </div>
    </template>

    <div class="flex gap-3">
        <button type="button" @click="addRow(false)" class="text-theme-xs font-medium text-brand-500 hover:text-brand-600">+ {{ __('O‘quvchi qo‘shish') }}</button>
        <button type="button" @click="addRow(true)" class="text-theme-xs font-medium text-brand-500 hover:text-brand-600">+ {{ __('Tashqi ishtirokchi qo‘shish') }}</button>
    </div>

    @if ($help)<p class="mt-1.5 text-theme-xs text-gray-400">{{ $help }}</p>@endif
</div>
