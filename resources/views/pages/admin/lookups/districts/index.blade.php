@extends('layouts.admin')

@section('title', __('Tumanlar'))

@section('content')
    @php
        $rows = $districts->map(fn ($d) => [
            'id' => $d->id,
            'name' => $d->name,
            'parent_id' => $d->region_id,
            'parent' => $d->region?->name,
            'update_url' => route('admin.lookups.districts.update', $d),
            'destroy_url' => route('admin.lookups.districts.destroy', $d),
        ])->values();
    @endphp

    <div
        x-data="lookupTable({
            storeUrl: '{{ route('admin.lookups.districts.store') }}',
            hasParent: true,
        })"
    >
        <x-admin.lookups.header
            :title="__('Tumanlar')"
            :count="$districts->count()"
            :add-label="__('Yangi tuman')" />

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        <x-admin.lookups.table :has-parent="true" :parent-label="__('Viloyat')">
            @forelse ($rows as $row)
                <x-admin.lookups.simple-parent-row :row="$row" />
            @empty
                <x-admin.lookups.empty :colspan="3" :message="__('Tumanlar topilmadi.')" />
            @endforelse
        </x-admin.lookups.table>

        <x-admin.lookups.simple-parent-modal :title="__('Tuman')" :parents="$regions" :parent-label="__('Viloyat')" :no-parent-label="__('Tanlanmagan')" />
    </div>
@endsection
