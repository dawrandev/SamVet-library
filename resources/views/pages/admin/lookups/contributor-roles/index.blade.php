@extends('layouts.admin')

@section('title', __('Mualliflik rollari'))

@section('content')
    @php
        $rows = $roles->map(fn ($r) => [
            'id' => $r->id,
            'name' => $r->name,
            'update_url' => route('admin.lookups.contributor-roles.update', $r),
            'destroy_url' => route('admin.lookups.contributor-roles.destroy', $r),
        ])->values();
    @endphp

    <div
        x-data="lookupTable({
            storeUrl: '{{ route('admin.lookups.contributor-roles.store') }}',
        })"
    >
        <x-admin.lookups.header
            :title="__('Mualliflik rollari')"
            :count="$roles->count()"
            :add-label="__('Yangi rol')" />

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        <x-admin.lookups.table>
            @forelse ($rows as $row)
                <x-admin.lookups.simple-row :row="$row" />
            @empty
                <x-admin.lookups.empty :colspan="2" :message="__('Rollar topilmadi.')" />
            @endforelse
        </x-admin.lookups.table>

        <x-admin.lookups.simple-modal :title="__('Rol')" />
    </div>
@endsection
