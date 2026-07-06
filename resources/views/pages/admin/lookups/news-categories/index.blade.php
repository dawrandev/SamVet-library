@extends('layouts.admin')

@section('title', __('Yangilik kategoriyalari'))

@section('content')
    @php
        $rows = $newsCategories->map(function ($c) {
            $tr = $c->getTranslations('name');
            return [
                'id' => $c->id,
                'uz' => $tr['uz'] ?? '',
                'ru' => $tr['ru'] ?? '',
                'kk' => $tr['kk'] ?? '',
                'incomplete' => empty($tr['ru']) || empty($tr['kk']),
                'update_url' => route('admin.lookups.news-categories.update', $c),
                'destroy_url' => route('admin.lookups.news-categories.destroy', $c),
            ];
        })->values();
    @endphp

    <div
        x-data="lookupTable({
            storeUrl: '{{ route('admin.lookups.news-categories.store') }}',
            translatable: true,
        })"
    >
        <x-admin.lookups.header
            :title="__('Yangilik kategoriyalari')"
            :count="$newsCategories->count()"
            :add-label="__('Yangi kategoriya')" />

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        <x-admin.lookups.table :translatable="true">
            @forelse ($rows as $row)
                <x-admin.lookups.translatable-row :row="$row" />
            @empty
                <x-admin.lookups.empty :colspan="3" :message="__('Kategoriyalar topilmadi.')" />
            @endforelse
        </x-admin.lookups.table>

        <x-admin.lookups.translatable-modal :title="__('Yangilik kategoriyasi')" />
    </div>
@endsection
