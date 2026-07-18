@extends('layouts.admin')

@section('title', __('Fan nomlari'))

@section('content')
    @php
        $rows = $fields->map(fn ($f) => [
            'id' => $f->id,
            'name' => $f->name,
            'update_url' => route('admin.lookups.science-fields.update', $f),
            'destroy_url' => route('admin.lookups.science-fields.destroy', $f),
        ])->values();
    @endphp

    <div
        x-data="lookupTable({
            storeUrl: '{{ route('admin.lookups.science-fields.store') }}',
        })"
    >
        <x-admin.lookups.header
            :title="__('Fan nomlari')"
            :count="$fields->count()"
            :add-label="__('Yangi fan')" />

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        <x-admin.lookups.table>
            @forelse ($rows as $row)
                <x-admin.lookups.simple-row :row="$row" />
            @empty
                <x-admin.lookups.empty :colspan="2" :message="__('Fan nomlari topilmadi.')" />
            @endforelse
        </x-admin.lookups.table>

        <x-admin.lookups.simple-modal :title="__('Fan nomi')" />
    </div>
@endsection
