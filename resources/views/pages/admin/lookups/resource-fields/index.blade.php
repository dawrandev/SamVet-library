@extends('layouts.admin')

@section('title', __('Resurs sohalari'))

@section('content')
    @php
        $rows = $resourceFields->map(function ($f) {
            $tr = $f->getTranslations('name');
            return [
                'id' => $f->id,
                'uz' => $tr['uz'] ?? '',
                'ru' => $tr['ru'] ?? '',
                'kk' => $tr['kk'] ?? '',
                'incomplete' => empty($tr['ru']) || empty($tr['kk']),
                'update_url' => route('admin.lookups.resource-fields.update', $f),
                'destroy_url' => route('admin.lookups.resource-fields.destroy', $f),
            ];
        })->values();
    @endphp

    <div
        x-data="lookupTable({
            storeUrl: '{{ route('admin.lookups.resource-fields.store') }}',
            translatable: true,
        })"
    >
        <x-admin.lookups.header
            :title="__('Resurs sohalari')"
            :count="$resourceFields->count()"
            :add-label="__('Yangi soha')" />

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        <x-admin.lookups.table :translatable="true">
            @forelse ($rows as $row)
                <x-admin.lookups.translatable-row :row="$row" />
            @empty
                <x-admin.lookups.empty :colspan="3" :message="__('Sohalar topilmadi.')" />
            @endforelse
        </x-admin.lookups.table>

        <x-admin.lookups.translatable-modal :title="__('Resurs sohasi')" />
    </div>
@endsection
