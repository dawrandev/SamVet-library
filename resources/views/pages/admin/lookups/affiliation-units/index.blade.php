@extends('layouts.admin')

@section('title', __('Mutaxassislik/bo‘limlar'))

@section('content')
    @php
        $rows = $affiliationUnits->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'update_url' => route('admin.lookups.affiliation-units.update', $u),
            'destroy_url' => route('admin.lookups.affiliation-units.destroy', $u),
        ])->values();
    @endphp

    <div
        x-data="lookupTable({
            storeUrl: '{{ route('admin.lookups.affiliation-units.store') }}',
        })"
    >
        <x-admin.lookups.header
            :title="__('Mutaxassislik/bo‘limlar')"
            :count="$affiliationUnits->count()"
            :add-label="__('Yangi mutaxassislik/bo‘lim')" />

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        <x-admin.lookups.table>
            @forelse ($rows as $row)
                <x-admin.lookups.simple-row :row="$row" />
            @empty
                <x-admin.lookups.empty :colspan="2" :message="__('Topilmadi.')" />
            @endforelse
        </x-admin.lookups.table>

        <x-admin.lookups.simple-modal :title="__('Mutaxassislik/bo‘lim')" />
    </div>
@endsection
