@extends('layouts.admin')

@section('title', __('Kafedralar ta’minganligi'))

@section('content')
    @php
        $rows = $departmentCoverages->map(function ($d) {
            $tr = $d->getTranslations('name');
            return [
                'id' => $d->id,
                'uz' => $tr['uz'] ?? '',
                'ru' => $tr['ru'] ?? '',
                'kk' => $tr['kk'] ?? '',
                'percentage' => $d->percentage,
                'incomplete' => empty($tr['ru']) || empty($tr['kk']),
                'update_url' => route('admin.lookups.department-coverages.update', $d),
                'destroy_url' => route('admin.lookups.department-coverages.destroy', $d),
            ];
        })->values();
    @endphp

    <div
        x-data="lookupTable({
            storeUrl: '{{ route('admin.lookups.department-coverages.store') }}',
            translatable: true,
            hasPercentage: true,
        })"
    >
        <x-admin.lookups.header
            :title="__('Kafedralar ta’minganligi')"
            :count="$departmentCoverages->count()"
            :add-label="__('Yangi kafedra')" />

        <p class="-mt-3 mb-5 text-sm text-gray-500 dark:text-gray-400">
            {{ __('Public sayttagi «Statistika» sahifasida «Kafedralar kesimida ta’minganlik darajasi» bo‘limida ko‘rinadi.') }}
        </p>

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        <x-admin.lookups.table :translatable="true" :has-percentage="true">
            @forelse ($rows as $row)
                <x-admin.lookups.translatable-row :row="$row" :has-percentage="true" />
            @empty
                <x-admin.lookups.empty :colspan="4" :message="__('Kafedralar topilmadi.')" />
            @endforelse
        </x-admin.lookups.table>

        <x-admin.lookups.translatable-modal :title="__('Kafedra')" :has-percentage="true" />
    </div>
@endsection
