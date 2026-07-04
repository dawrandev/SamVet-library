@extends('layouts.admin')

@section('title', __('Jurnal turlari'))

@section('content')
    @php
        // Modal (qo'shish/tahrirlash) uchun tarjimalarni tayyorlash
        $rows = $journalTypes->map(function ($t) {
            $tr = $t->getTranslations('name');
            return [
                'id' => $t->id,
                'uz' => $tr['uz'] ?? '',
                'ru' => $tr['ru'] ?? '',
                'kk' => $tr['kk'] ?? '',
                'incomplete' => empty($tr['ru']) || empty($tr['kk']),
                'update_url' => route('admin.lookups.journal-types.update', $t),
                'destroy_url' => route('admin.lookups.journal-types.destroy', $t),
            ];
        })->values();
    @endphp

    <div
        x-data="lookupTable({
            storeUrl: '{{ route('admin.lookups.journal-types.store') }}',
            translatable: true,
        })"
    >
        <x-admin.lookups.header
            :title="__('Jurnal turlari')"
            :count="$journalTypes->count()"
            :add-label="__('Yangi tur')" />

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        <x-admin.lookups.table :translatable="true">
            @forelse ($rows as $row)
                <x-admin.lookups.translatable-row :row="$row" />
            @empty
                <x-admin.lookups.empty :colspan="3" :message="__('Jurnal turlari topilmadi.')" />
            @endforelse
        </x-admin.lookups.table>

        <x-admin.lookups.translatable-modal :title="__('Jurnal turi')" />
    </div>
@endsection
