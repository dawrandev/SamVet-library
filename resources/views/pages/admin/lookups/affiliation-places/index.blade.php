@extends('layouts.admin')

@section('title', __('O‘qish/ish joylari'))

@section('content')
    @php
        $rows = $affiliationPlaces->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'update_url' => route('admin.lookups.affiliation-places.update', $p),
            'destroy_url' => route('admin.lookups.affiliation-places.destroy', $p),
        ])->values();
    @endphp

    <div
        x-data="lookupTable({
            storeUrl: '{{ route('admin.lookups.affiliation-places.store') }}',
        })"
    >
        <x-admin.lookups.header
            :title="__('O‘qish/ish joylari')"
            :count="$affiliationPlaces->count()"
            :add-label="__('Yangi joy')" />

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        <x-admin.lookups.table>
            @forelse ($rows as $row)
                <x-admin.lookups.simple-row :row="$row" />
            @empty
                <x-admin.lookups.empty :colspan="2" :message="__('Joylar topilmadi.')" />
            @endforelse
        </x-admin.lookups.table>

        <x-admin.lookups.simple-modal :title="__('O‘qish/ish joyi')" />
    </div>
@endsection
