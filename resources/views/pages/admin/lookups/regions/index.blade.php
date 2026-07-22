@extends('layouts.admin')

@section('title', __('Viloyatlar'))

@section('content')
    @php
        $rows = $regions->map(fn ($r) => [
            'id' => $r->id,
            'name' => $r->name,
            'update_url' => route('admin.lookups.regions.update', $r),
            'destroy_url' => route('admin.lookups.regions.destroy', $r),
        ])->values();
    @endphp

    <div
        x-data="lookupTable({
            storeUrl: '{{ route('admin.lookups.regions.store') }}',
        })"
    >
        <x-admin.lookups.header
            :title="__('Viloyatlar')"
            :count="$regions->count()"
            :add-label="__('Yangi viloyat')" />

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        <x-admin.lookups.table>
            @forelse ($rows as $row)
                <x-admin.lookups.simple-row :row="$row" />
            @empty
                <x-admin.lookups.empty :colspan="2" :message="__('Viloyatlar topilmadi.')" />
            @endforelse
        </x-admin.lookups.table>

        <x-admin.lookups.simple-modal :title="__('Viloyat')" />
    </div>
@endsection
