@extends('layouts.admin')

@section('title', __('Nashriyotlar'))

@section('content')
    @php
        $rows = $publishers->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'update_url' => route('admin.lookups.publishers.update', $p),
            'destroy_url' => route('admin.lookups.publishers.destroy', $p),
        ])->values();
    @endphp

    <div
        x-data="lookupTable({
            storeUrl: '{{ route('admin.lookups.publishers.store') }}',
        })"
    >
        <x-admin.lookups.header
            :title="__('Nashriyotlar')"
            :count="$publishers->count()"
            :add-label="__('Yangi nashriyot')" />

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        <x-admin.lookups.table>
            @forelse ($rows as $row)
                <x-admin.lookups.simple-row :row="$row" />
            @empty
                <x-admin.lookups.empty :colspan="2" :message="__('Nashriyotlar topilmadi.')" />
            @endforelse
        </x-admin.lookups.table>

        <x-admin.lookups.simple-modal :title="__('Nashriyot')" />
    </div>
@endsection
