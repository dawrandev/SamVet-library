@extends('layouts.admin')

@section('title', __('Kitob turlari'))

@section('content')
    @php
        // Modal (qo'shish/tahrirlash) uchun tarjimalarni tayyorlash
        $rows = $bookTypes->map(function ($t) {
            $tr = $t->getTranslations('name');
            return [
                'id' => $t->id,
                'uz' => $tr['uz'] ?? '',
                'ru' => $tr['ru'] ?? '',
                'kk' => $tr['kk'] ?? '',
                'incomplete' => empty($tr['ru']) || empty($tr['kk']),
                'update_url' => route('admin.lookups.book-types.update', $t),
                'destroy_url' => route('admin.lookups.book-types.destroy', $t),
            ];
        })->values();
    @endphp

    <div
        x-data="lookupTable({
            storeUrl: '{{ route('admin.lookups.book-types.store') }}',
            translatable: true,
        })"
    >
        <x-admin.lookups.header
            :title="__('Kitob turlari')"
            :count="$bookTypes->count()"
            :add-label="__('Yangi tur')" />

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        <x-admin.lookups.table :translatable="true">
            @forelse ($rows as $row)
                <x-admin.lookups.translatable-row :row="$row" />
            @empty
                <x-admin.lookups.empty :colspan="3" :message="__('Kitob turlari topilmadi.')" />
            @endforelse
        </x-admin.lookups.table>

        <x-admin.lookups.translatable-modal :title="__('Kitob turi')" />
    </div>
@endsection
