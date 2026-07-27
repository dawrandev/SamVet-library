@extends('layouts.admin')

@section('title', __('Kategoriyalar'))

@section('content')
    @php
        // Position within its own sibling group (same parent_id) — already
        // ordered by sort_order — to know when to disable/hide up/down.
        $siblingIndex = [];
        foreach ($categories->groupBy('parent_id') as $group) {
            foreach ($group->values() as $i => $c) {
                $siblingIndex[$c->id] = ['index' => $i, 'count' => $group->count()];
            }
        }

        $rows = $categories->map(function ($c) use ($siblingIndex) {
            $tr = $c->getTranslations('name');
            $pos = $siblingIndex[$c->id];

            return [
                'id' => $c->id,
                'uz' => $tr['uz'] ?? '',
                'ru' => $tr['ru'] ?? '',
                'kk' => $tr['kk'] ?? '',
                'parent_id' => $c->parent_id,
                'parent' => $c->parent?->name,
                'incomplete' => empty($tr['ru']) || empty($tr['kk']),
                'update_url' => route('admin.lookups.categories.update', $c),
                'destroy_url' => route('admin.lookups.categories.destroy', $c),
                'move_up_url' => $pos['index'] > 0 ? route('admin.lookups.categories.move-up', $c) : null,
                'move_down_url' => $pos['index'] < $pos['count'] - 1 ? route('admin.lookups.categories.move-down', $c) : null,
            ];
        })->values();
    @endphp

    <div
        x-data="lookupTable({
            storeUrl: '{{ route('admin.lookups.categories.store') }}',
            translatable: true,
            hasParent: true,
        })"
    >
        <x-admin.lookups.header
            :title="__('Kategoriyalar')"
            :count="$categories->count()"
            :add-label="__('Yangi kategoriya')" />

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        <x-admin.lookups.table :translatable="true" :has-parent="true">
            @forelse ($rows as $row)
                <x-admin.lookups.translatable-row :row="$row" :has-parent="true" :sortable="true" />
            @empty
                <x-admin.lookups.empty :colspan="4" :message="__('Kategoriyalar topilmadi.')" />
            @endforelse
        </x-admin.lookups.table>

        <x-admin.lookups.translatable-modal :title="__('Kategoriya')" :parents="$parents" />
    </div>
@endsection
