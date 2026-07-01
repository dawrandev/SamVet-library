@extends('layouts.admin')

@section('title', __('Tillar'))

@section('content')
    @php
        $rows = $languages->map(function ($l) {
            $tr = $l->getTranslations('name');
            return [
                'id' => $l->id,
                'uz' => $tr['uz'] ?? '',
                'ru' => $tr['ru'] ?? '',
                'kk' => $tr['kk'] ?? '',
                'incomplete' => empty($tr['ru']) || empty($tr['kk']),
                'update_url' => route('admin.lookups.languages.update', $l),
                'destroy_url' => route('admin.lookups.languages.destroy', $l),
            ];
        })->values();
    @endphp

    <div
        x-data="lookupTable({
            storeUrl: '{{ route('admin.lookups.languages.store') }}',
            translatable: true,
        })"
    >
        <x-admin.lookups.header
            :title="__('Tillar')"
            :count="$languages->count()"
            :add-label="__('Yangi til')" />

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        <x-admin.lookups.table :translatable="true">
            @forelse ($rows as $row)
                <x-admin.lookups.translatable-row :row="$row" />
            @empty
                <x-admin.lookups.empty :colspan="3" :message="__('Tillar topilmadi.')" />
            @endforelse
        </x-admin.lookups.table>

        <x-admin.lookups.translatable-modal :title="__('Til')" />
    </div>
@endsection
