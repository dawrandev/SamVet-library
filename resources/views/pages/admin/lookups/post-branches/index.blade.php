@extends('layouts.admin')

@section('title', __('Pochta filiallari'))

@section('content')
    @php
        $rows = $branches->map(fn ($b) => [
            'id' => $b->id,
            'name' => $b->name,
            'update_url' => route('admin.lookups.post-branches.update', $b),
            'destroy_url' => route('admin.lookups.post-branches.destroy', $b),
        ])->values();
    @endphp

    <div
        x-data="lookupTable({
            storeUrl: '{{ route('admin.lookups.post-branches.store') }}',
        })"
    >
        <x-admin.lookups.header
            :title="__('Pochta filiallari')"
            :count="$branches->count()"
            :add-label="__('Yangi filial')" />

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        <x-admin.lookups.table>
            @forelse ($rows as $row)
                <x-admin.lookups.simple-row :row="$row" />
            @empty
                <x-admin.lookups.empty :colspan="2" :message="__('Pochta filiallari topilmadi.')" />
            @endforelse
        </x-admin.lookups.table>

        <x-admin.lookups.simple-modal :title="__('Pochta filiali')" />
    </div>
@endsection
