@props([
    'roles',        // Collection<ContributorRole> — id, name
    'value' => [],  // [['contributor_role_id' => .., 'name' => ..], ...]
    'label' => null,
    'help' => null,
])

@php
    $base = 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 border-gray-300 dark:border-gray-700';
    $initialRows = collect($value)
        ->map(fn ($c) => [
            'contributor_role_id' => (string) ($c['contributor_role_id'] ?? ''),
            'name' => $c['name'] ?? '',
        ])
        ->values();
@endphp

{{-- Unlimited rows, each a role + free-typed full name — no existing repeatable-row
     precedent in this codebase, so this is a fresh, minimal Alpine array pattern
     (same house idiom as the multiselect component's `<template x-for>` chips). --}}
<div x-data="{
        rows: @js($initialRows),
        addRow() { this.rows.push({ contributor_role_id: '', name: '' }); },
        removeRow(i) { this.rows.splice(i, 1); },
    }"
>
    @if ($label)
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ $label }}</label>
    @endif

    <template x-for="(row, i) in rows" :key="i">
        <div class="mb-2 flex items-start gap-2">
            <select :name="`contributors[${i}][contributor_role_id]`" x-model="row.contributor_role_id"
                    class="{{ $base }} sm:w-56">
                <option value="">{{ __('Rolni tanlang') }}</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
            <input type="text" :name="`contributors[${i}][name]`" x-model="row.name"
                   placeholder="{{ __('Ism sharifi') }}" autocomplete="off"
                   class="{{ $base }}" />
            <button type="button" @click="removeRow(i)"
                    class="flex h-11 w-11 flex-none items-center justify-center rounded-lg border border-gray-300 text-gray-400 hover:bg-gray-50 hover:text-error-500 dark:border-gray-700 dark:hover:bg-white/5">&times;</button>
        </div>
    </template>

    <button type="button" @click="addRow()"
            class="text-theme-xs font-medium text-brand-500 hover:text-brand-600">+ {{ __('Ishtirokchi qo‘shish') }}</button>

    @if ($help)<p class="mt-1.5 text-theme-xs text-gray-400">{{ $help }}</p>@endif
</div>
