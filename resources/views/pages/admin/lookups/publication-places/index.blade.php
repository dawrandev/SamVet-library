@extends('layouts.admin')

@section('title', __('Nashriyot joylari'))

@section('content')
    @php
        $rows = $places->map(function ($place) {
            $tr = $place->getTranslations('name');

            return [
                'id' => $place->id,
                'uz' => $tr['uz'] ?? '',
                'ru' => $tr['ru'] ?? '',
                'kk' => $tr['kk'] ?? '',
                'incomplete' => empty($tr['ru']) || empty($tr['kk']),
                'update_url' => route('admin.lookups.publication-places.update', $place),
                'destroy_url' => route('admin.lookups.publication-places.destroy', $place),
            ];
        })->values();
    @endphp

    <div
        x-data="lookupTable({
            storeUrl: '{{ route('admin.lookups.publication-places.store') }}',
            translatable: true,
        })"
    >
        <x-admin.lookups.header
            :title="__('Nashriyot joylari')"
            :count="$places->count()"
            :add-label="__('Yangi nashriyot joyi')" />

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        <x-admin.lookups.table :translatable="true">
            @forelse ($rows as $row)
                <x-admin.lookups.translatable-row :row="$row" />
            @empty
                <x-admin.lookups.empty :colspan="3" :message="__('Nashriyot joylari topilmadi.')" />
            @endforelse
        </x-admin.lookups.table>

        <x-admin.lookups.translatable-modal :title="__('Nashriyot joyi')" />
    </div>
@endsection
