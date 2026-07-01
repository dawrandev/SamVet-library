@extends('layouts.admin')

@section('title', __('Mualliflar'))

@section('content')
    @php
        $rows = $authors->map(fn ($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'update_url' => route('admin.lookups.authors.update', $a),
            'destroy_url' => route('admin.lookups.authors.destroy', $a),
        ])->values();
    @endphp

    <div
        x-data="lookupTable({
            storeUrl: '{{ route('admin.lookups.authors.store') }}',
        })"
    >
        <x-admin.lookups.header
            :title="__('Mualliflar')"
            :count="$authors->count()"
            :add-label="__('Yangi muallif')" />

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        <x-admin.lookups.table>
            @forelse ($rows as $row)
                <x-admin.lookups.simple-row :row="$row" />
            @empty
                <x-admin.lookups.empty :colspan="2" :message="__('Mualliflar topilmadi.')" />
            @endforelse
        </x-admin.lookups.table>

        <x-admin.lookups.simple-modal :title="__('Muallif')" />
    </div>
@endsection
