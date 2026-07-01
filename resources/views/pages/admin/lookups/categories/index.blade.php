@extends('layouts.admin')

@section('title', __('Kategoriyalar'))

@section('content')
    @php
        $rows = $categories->map(function ($c) {
            $tr = $c->getTranslations('name');
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
                <x-admin.lookups.translatable-row :row="$row" :has-parent="true" />
            @empty
                <x-admin.lookups.empty :colspan="4" :message="__('Kategoriyalar topilmadi.')" />
            @endforelse
        </x-admin.lookups.table>

        <x-admin.lookups.translatable-modal :title="__('Kategoriya')" :parents="$parents" />
    </div>
@endsection
