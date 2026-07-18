@extends('layouts.admin')

@section('title', __('Mutaxassisliklar'))

@section('content')
    @php
        $rows = $specialties->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'update_url' => route('admin.lookups.master-specialties.update', $s),
            'destroy_url' => route('admin.lookups.master-specialties.destroy', $s),
        ])->values();
    @endphp

    <div
        x-data="lookupTable({
            storeUrl: '{{ route('admin.lookups.master-specialties.store') }}',
        })"
    >
        <x-admin.lookups.header
            :title="__('Mutaxassisliklar (shifri va nomi)')"
            :count="$specialties->count()"
            :add-label="__('Yangi mutaxassislik')" />

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        <x-admin.lookups.table>
            @forelse ($rows as $row)
                <x-admin.lookups.simple-row :row="$row" />
            @empty
                <x-admin.lookups.empty :colspan="2" :message="__('Mutaxassisliklar topilmadi.')" />
            @endforelse
        </x-admin.lookups.table>

        <x-admin.lookups.simple-modal :title="__('Mutaxassislik')" />
    </div>
@endsection
