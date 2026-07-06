@extends('layouts.admin')

@section('title', __('Sahifa') . ' — ' . $menuItem->getTranslation('title', 'uz'))

@section('content')
    @php
        $bodyVal = $page ? $page->getTranslations('body') : [];
        $coverUrl = $page && $page->cover_image ? asset('storage/' . $page->cover_image) : null;
    @endphp

    <form method="POST" action="{{ route('admin.menu-items.page.update', $menuItem) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Sarlavha + amallar --}}
        <div class="mb-6 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.menu-items.index') }}" class="flex h-9 w-9 flex-none items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
                <div>
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">{{ __('Sahifa matni') }}</h2>
                    <p class="text-theme-sm text-gray-500 dark:text-gray-400">
                        {{ __('Menyu') }}: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $menuItem->getTranslation('title', 'uz') }}</span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.menu-items.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</a>
                <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 rounded-lg px-5 py-2 text-sm font-medium text-white transition">{{ __('Saqlash') }}</button>
            </div>
        </div>

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif
        @if ($errors->any())
            <x-alert type="error" class="mb-6">{{ __('Iltimos, formadagi xatolarni to‘g‘rilang.') }}</x-alert>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Chap: matn (editor) --}}
            <div class="lg:col-span-2">
                <x-admin.form.section :title="__('Matn')">
                    <x-admin.form.rich-editor name="body" :value="$bodyVal"
                        :help="__('Sahifa mazmuni — 3 tilda. Sarlavha sifatida menyu nomi ishlatiladi.')" />
                </x-admin.form.section>
            </div>

            {{-- O'ng: muqova --}}
            <div class="lg:col-span-1">
                <x-admin.form.section :title="__('Muqova')">
                    <x-admin.form.file name="cover" :label="__('Muqova rasmi')" :image="true" accept="image/jpeg,image/png,image/webp,image/gif"
                        :currentUrl="$coverUrl"
                        :help="__('Ixtiyoriy. JPG/PNG/WebP, 2 MB gacha.')" />
                </x-admin.form.section>
            </div>
        </div>
    </form>
@endsection
