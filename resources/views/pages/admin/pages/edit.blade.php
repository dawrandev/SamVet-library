@extends('layouts.admin')

@section('title', __('Sahifa') . ' — ' . $menuItem->getTranslation('title', 'uz'))

@section('content')
    @php
        $titleVal = $page ? $page->getTranslations('title') : [];
        $bodyVal = $page ? $page->getTranslations('body') : [];
        $coverUrl = $page && $page->cover_image ? asset('storage/' . $page->cover_image) : null;
    @endphp

    <form method="POST" action="{{ route('admin.menu-items.page.update', $menuItem) }}" enctype="multipart/form-data"
          x-data="uploadForm" @submit="submitUpload($event)">
        @csrf
        @method('PUT')

        <x-admin.form.upload-errors />
        <x-admin.form.uploading-overlay />

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
                @if ($page)
                    <a href="{{ route('admin.menu-items.page.show', $menuItem) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Ko‘rish') }}</a>
                @endif
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
            {{-- Chap: sarlavha + matn (editor) --}}
            <div class="space-y-6 lg:col-span-2">
                <x-admin.form.section :title="__('Sarlavha')">
                    <x-admin.form.translatable-tabs name="title" :value="$titleVal"
                        :placeholders="['uz' => __('Sarlavha (o‘zbekcha)'), 'ru' => __('Заголовок (рус)'), 'kk' => __('Sarlawḳa (qq)')]"
                        :help="__('Ixtiyoriy. Bo‘sh qoldirilsa, menyu nomi sarlavha sifatida ishlatiladi.')" />
                </x-admin.form.section>

                <x-admin.form.section :title="__('Matn')">
                    <x-admin.form.rich-editor name="body" :value="$bodyVal"
                        :help="__('Sahifa mazmuni — 3 tilda.')" />
                </x-admin.form.section>
            </div>

            {{-- O'ng: media --}}
            <div class="space-y-6 lg:col-span-1">
                <x-admin.form.section :title="__('Muqova')">
                    <x-admin.form.file name="cover" :label="__('Muqova rasmi')" :image="true" accept="image/jpeg,image/png,image/webp,image/gif" with-progress removable
                        :currentUrl="$coverUrl"
                        :help="__('Ixtiyoriy. JPG/PNG/WebP, 2 MB gacha.')" />
                </x-admin.form.section>

                <x-admin.form.section :title="__('Galereya')">
                    <div x-data="{ removedGalleryIds: [] }">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Rasmlar (bir nechta)') }}</label>
                        @if ($page && $page->images->isNotEmpty())
                            <div class="mb-2 flex flex-wrap gap-2">
                                @foreach ($page->images as $img)
                                    <div x-show="! removedGalleryIds.includes({{ $img->id }})" class="relative">
                                        <img src="{{ asset('storage/' . $img->path) }}" alt="" class="h-16 w-16 rounded-lg border border-gray-200 object-cover dark:border-gray-800" />
                                        <button type="button" @click="removedGalleryIds.push({{ $img->id }})"
                                                class="absolute -right-2 -top-2 flex h-5 w-5 items-center justify-center rounded-full bg-gray-800/80 text-xs text-white hover:bg-error-500"
                                                :title="'{{ __('O‘chirish') }}'">&times;</button>
                                    </div>
                                @endforeach
                            </div>
                            {{-- Only the actually-removed ids are ever submitted — a static
                                 per-image hidden input would still POST even while hidden by x-show. --}}
                            <template x-for="id in removedGalleryIds" :key="id">
                                <input type="hidden" name="remove_gallery_ids[]" :value="id" />
                            </template>
                            <p x-show="removedGalleryIds.length > 0" x-cloak class="mb-2 text-theme-xs text-error-500">
                                {{ __('Belgilangan rasmlar saqlaganda o‘chiriladi.') }}
                            </p>
                        @endif
                        <input type="file" name="gallery[]" multiple accept="image/jpeg,image/png,image/webp,image/gif"
                               class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-600 hover:file:bg-brand-100 dark:text-gray-400 dark:file:bg-brand-500/15 dark:file:text-brand-400" />
                        <p class="mt-1 text-theme-xs text-gray-400">{{ __('Bir marta tanlanadi (tilga bog‘liq emas). Yangi rasmlar mavjudlariga qo‘shiladi.') }}</p>
                        @error('gallery.*')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>
                </x-admin.form.section>
            </div>
        </div>
    </form>
@endsection
