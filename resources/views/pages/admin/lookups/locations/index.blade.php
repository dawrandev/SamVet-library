@extends('layouts.admin')

@section('title', __('Joylashuvlar'))

@section('content')
    @php
        $rows = $locations->map(function ($loc) {
            $tr = $loc->getTranslations('name');
            return [
                'id' => $loc->id,
                'uz' => $tr['uz'] ?? '',
                'ru' => $tr['ru'] ?? '',
                'kk' => $tr['kk'] ?? '',
                'incomplete' => empty($tr['ru']) || empty($tr['kk']),
                'update_url' => route('admin.lookups.locations.update', $loc),
                'destroy_url' => route('admin.lookups.locations.destroy', $loc),
            ];
        })->values();
    @endphp

    <div
        x-data="lookupTable({
            storeUrl: '{{ route('admin.lookups.locations.store') }}',
            translatable: true,
        })"
    >
        <x-admin.lookups.header
            :title="__('Joylashuvlar')"
            :count="$locations->count()"
            :add-label="__('Yangi joylashuv')" />

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        <x-admin.lookups.table :translatable="true">
            @forelse ($rows as $row)
                <x-admin.lookups.translatable-row :row="$row" />
            @empty
                <x-admin.lookups.empty :colspan="3" :message="__('Joylashuvlar topilmadi.')" />
            @endforelse
        </x-admin.lookups.table>

        <x-admin.lookups.translatable-modal :title="__('Joylashuv')" />
    </div>
@endsection
