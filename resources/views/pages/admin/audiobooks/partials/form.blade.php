@php
    $audiobook = $audiobook ?? null;
@endphp

<form method="POST"
      action="{{ $audiobook ? route('admin.audiobooks.update', $audiobook) : route('admin.audiobooks.store') }}"
      enctype="multipart/form-data" class="space-y-6"
      x-data="uploadForm" @submit="submitUpload($event)">
    @csrf
    @if ($audiobook) @method('PUT') @endif

    <x-admin.form.upload-errors />
    <x-admin.form.uploading-overlay />

    {{-- Header + actions (sticky) --}}
    <div class="sticky top-16 z-9 -mx-4 mb-6 flex items-center justify-between border-b border-gray-200 bg-gray-50/90 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 dark:border-gray-800 dark:bg-gray-900/90">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.audiobooks.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">
                {{ $audiobook ? __('Audiokitobni tahrirlash') : __('Yangi audiokitob') }}
            </h2>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.audiobooks.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</a>
            <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 rounded-lg px-5 py-2 text-sm font-medium text-white transition">{{ __('Saqlash') }}</button>
        </div>
    </div>

    @if ($errors->any())
        <x-alert type="error" class="mb-6">{{ __('Iltimos, formadagi xatolarni to‘g‘rilang.') }}</x-alert>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
        <x-admin.form.section :title="__('Muqova')">
            <x-admin.form.file name="cover" :image="true" accept="image/*" with-progress
                :currentUrl="$audiobook?->cover_image ? asset('storage/' . $audiobook->cover_image) : null"
                :help="__('JPG/PNG, 2 MB gacha')" removable />
        </x-admin.form.section>

        <x-admin.form.section :title="__('Ma’lumotlari')">
            <div class="space-y-5">
                <x-admin.form.input name="name" :label="__('Audio nomi')" :value="$audiobook?->name" required :placeholder="__('Audiokitob nomi')" />
                <x-admin.form.input name="author" :label="__('Muallifi')" :value="$audiobook?->author" :placeholder="__('masalan: Abdulla Qodiriy')" />

                <div>
                    <label for="annotation" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Annotatsiyasi') }}</label>
                    <textarea name="annotation" id="annotation" rows="6"
                              class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 {{ $errors->has('annotation') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">{{ old('annotation', $audiobook?->annotation) }}</textarea>
                    @error('annotation')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                </div>
            </div>
        </x-admin.form.section>
    </div>
</form>
