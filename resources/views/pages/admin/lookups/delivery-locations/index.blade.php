@extends('layouts.admin')

@section('title', __('Yetkazib berish manzillari'))

@section('content')
    @php
        $rows = $locations->map(fn ($l) => [
            'id' => $l->id,
            'name' => $l->name,
            'update_url' => route('admin.lookups.delivery-locations.update', $l),
            'destroy_url' => route('admin.lookups.delivery-locations.destroy', $l),
        ])->values();
    @endphp

    <div
        x-data="lookupTable({
            storeUrl: '{{ route('admin.lookups.delivery-locations.store') }}',
        })"
    >
        <x-admin.lookups.header
            :title="__('Yetkazib berish manzillari')"
            :count="$locations->count()"
            :add-label="__('Yangi manzil')" />

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        <x-admin.lookups.table>
            @forelse ($rows as $row)
                <x-admin.lookups.simple-row :row="$row" />
            @empty
                <x-admin.lookups.empty :colspan="2" :message="__('Manzillar topilmadi.')" />
            @endforelse
        </x-admin.lookups.table>

        <x-admin.lookups.simple-modal :title="__('Manzil')" />
    </div>
@endsection
