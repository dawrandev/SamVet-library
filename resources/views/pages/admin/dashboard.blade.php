@extends('layouts.admin')

@section('title', __('Bosh sahifa'))

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900">{{ __('Xush kelibsiz') }}, {{ auth()->user()->name }}! 👋</h2>
        <p class="mt-1 text-sm text-gray-500">{{ __('SamVet kutubxonasi boshqaruv paneliga umumiy nazar.') }}</p>
    </div>

    {{-- Statistika kartalari --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.stat-card :label="__('Jami kitoblar')" value="0" icon="📕" color="bg-indigo-50 text-indigo-600" />
        <x-admin.stat-card :label="__('Kategoriyalar')" value="0" icon="🗂️" color="bg-emerald-50 text-emerald-600" />
        <x-admin.stat-card :label="__('Foydalanuvchilar')" value="0" icon="👥" color="bg-amber-50 text-amber-600" />
        <x-admin.stat-card :label="__('Jami ko‘rishlar')" value="0" icon="👁️" color="bg-rose-50 text-rose-600" />
    </div>

    {{-- Bo'sh holat --}}
    <div class="mt-6 rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center">
        <p class="text-4xl">🚀</p>
        <h3 class="mt-3 text-base font-semibold text-gray-900">{{ __('Admin panel tayyor!') }}</h3>
        <p class="mt-1 text-sm text-gray-500">
            {{ __('Keyingi qadam: kitob, kategoriya va foydalanuvchi bo\'limlarini qo\'shamiz.') }}
        </p>
    </div>
@endsection
