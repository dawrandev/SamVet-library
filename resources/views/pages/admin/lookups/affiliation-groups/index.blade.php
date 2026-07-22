@extends('layouts.admin')

@section('title', __('Guruh/lavozimlar'))

@section('content')
    @php
        $rows = $affiliationGroups->map(fn ($g) => [
            'id' => $g->id,
            'name' => $g->name,
            'update_url' => route('admin.lookups.affiliation-groups.update', $g),
            'destroy_url' => route('admin.lookups.affiliation-groups.destroy', $g),
        ])->values();
    @endphp

    <div
        x-data="lookupTable({
            storeUrl: '{{ route('admin.lookups.affiliation-groups.store') }}',
        })"
    >
        <x-admin.lookups.header
            :title="__('Guruh/lavozimlar')"
            :count="$affiliationGroups->count()"
            :add-label="__('Yangi guruh/lavozim')" />

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

        <x-admin.lookups.simple-modal :title="__('Guruh/lavozim')" />
    </div>
@endsection
