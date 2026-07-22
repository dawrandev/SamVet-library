@extends('layouts.admin')

@section('title', $audiobook->name)

@section('content')
    @php
        // Flag to keep the track form open on server errors
        $openStore = $errors->any() && old('_track_form') === 'store';
        $openEditId = $errors->any() && old('_track_form') === 'edit' ? (int) old('_track_id') : null;
    @endphp

    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.audiobooks.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ $audiobook->name }}</h2>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.audiobooks.edit', $audiobook) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Tahrirlash') }}</a>
            <button type="button"
                    @click="$store.confirm.ask('{{ route('admin.audiobooks.destroy', $audiobook) }}', '{{ __('Audiokitobni o‘chirishni tasdiqlaysizmi?') }}')"
                    class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
        </div>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
    @endif

    <div class="grid grid-cols-12 gap-6">
        {{-- Left: audiobook details --}}
        <div class="col-span-12 space-y-6 xl:col-span-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <div class="mx-auto flex h-56 w-40 items-center justify-center overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-800">
                    @if ($audiobook->cover_image)
                        <img src="{{ asset('storage/' . $audiobook->cover_image) }}" alt="" class="h-full w-full object-cover" />
                    @else
                        <x-admin.icon name="speaker-wave" class="h-14 w-14 text-gray-300 dark:text-gray-600" />
                    @endif
                </div>

                <dl class="mt-5 space-y-3">
                    <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-800/50">
                        <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Audio nomi') }}</dt>
                        <dd class="text-theme-sm text-right font-medium text-gray-800 dark:text-white/90">{{ $audiobook->name }}</dd>
                    </div>
                    @if ($audiobook->author)
                        <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-800/50">
                            <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Muallifi') }}</dt>
                            <dd class="text-theme-sm text-right font-medium text-gray-800 dark:text-white/90">{{ $audiobook->author }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-4 pb-2">
                        <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Audiolar soni') }}</dt>
                        <dd class="text-theme-sm text-right font-medium text-gray-800 dark:text-white/90">{{ $audiobook->tracks->count() }}</dd>
                    </div>
                </dl>

                @if ($audiobook->annotation)
                    <div class="mt-5 border-t border-gray-100 pt-4 dark:border-gray-800">
                        <p class="text-theme-sm mb-1.5 font-medium text-gray-700 dark:text-gray-300">{{ __('Annotatsiyasi') }}</p>
                        <p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $audiobook->annotation }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Right: tracks --}}
        <div class="col-span-12 space-y-6 xl:col-span-8">
            <div
                x-data="{
                    showStore: {{ $openStore ? 'true' : 'false' }},
                    editId: {{ $openEditId ?? 'null' }},
                }"
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
            >
                <div class="flex items-center justify-between border-b border-gray-100 p-5 dark:border-gray-800 sm:px-6">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Audiolar') }}</h3>
                    <button type="button" @click="showStore = true"
                            class="bg-brand-500 hover:bg-brand-600 inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium text-white">
                        + {{ __('Audio qo‘shish') }}
                    </button>
                </div>
                <div class="max-w-full overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('№') }}</th>
                                <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Nomi') }}</th>
                                <th class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Amallar') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($audiobook->tracks as $i => $track)
                                <tr class="border-b border-gray-50 last:border-0 dark:border-gray-800/50">
                                    <td class="px-5 py-3 text-theme-sm text-gray-500 dark:text-gray-400">{{ $i + 1 }}</td>
                                    <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $track->title }}</td>
                                    <td class="px-5 py-3 text-right text-theme-xs">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" @click="editId = {{ $track->id }}"
                                                    class="rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800">{{ __('Tahrirlash') }}</button>
                                            <button type="button"
                                                    @click="$store.confirm.ask('{{ route('admin.audiobooks.tracks.destroy', [$audiobook, $track]) }}', '{{ __('Audioni o‘chirishni tasdiqlaysizmi?') }}')"
                                                    class="rounded-lg border border-red-200 px-3 py-1.5 font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-5 py-8 text-center text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Audio yo‘q') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Add modal --}}
                <div x-show="showStore" x-cloak
                     class="fixed inset-0 z-99999 flex items-center justify-center p-4"
                     @keydown.escape.window="showStore = false">
                    <div class="fixed inset-0 bg-gray-900/50" @click="showStore = false"></div>
                    <div class="relative z-10 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
                        <div class="mb-5 flex items-center justify-between">
                            <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Yangi audio') }}</h4>
                            <button type="button" @click="showStore = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
                        </div>
                        <form method="POST" action="{{ route('admin.audiobooks.tracks.store', $audiobook) }}" enctype="multipart/form-data" class="space-y-4"
                              x-data="uploadForm" @submit="submitUpload($event)">
                            @csrf
                            <input type="hidden" name="_track_form" value="store" />
                            <x-admin.form.upload-errors />
                            <x-admin.form.uploading-overlay />

                            <x-admin.form.input name="title" :label="__('Nomi')" required :placeholder="__('masalan: 1-qism')" />
                            <x-admin.form.file name="audio_file" :label="__('Audio fayl')" accept="audio/*" with-progress :help="__('MP3/WAV, 100 MB gacha')" />

                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" @click="showStore = false"
                                        class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">{{ __('Bekor qilish') }}</button>
                                <button type="submit"
                                        class="bg-brand-500 hover:bg-brand-600 rounded-lg px-4 py-2.5 text-sm font-medium text-white">{{ __('Saqlash') }}</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Edit modals (one per track) --}}
                @foreach ($audiobook->tracks as $track)
                    @php $isEditing = $openEditId === $track->id; @endphp
                    <div x-show="editId === {{ $track->id }}" x-cloak
                         class="fixed inset-0 z-99999 flex items-center justify-center p-4"
                         @keydown.escape.window="editId = null">
                        <div class="fixed inset-0 bg-gray-900/50" @click="editId = null"></div>
                        <div class="relative z-10 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
                            <div class="mb-5 flex items-center justify-between">
                                <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Audioni tahrirlash') }}</h4>
                                <button type="button" @click="editId = null" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
                            </div>
                            <form method="POST" action="{{ route('admin.audiobooks.tracks.update', [$audiobook, $track]) }}" enctype="multipart/form-data" class="space-y-4"
                                  x-data="uploadForm" @submit="submitUpload($event)">
                                @csrf @method('PUT')
                                <input type="hidden" name="_track_form" value="edit" />
                                <input type="hidden" name="_track_id" value="{{ $track->id }}" />
                                <x-admin.form.upload-errors />
                                <x-admin.form.uploading-overlay />

                                <x-admin.form.input name="title" :label="__('Nomi')" required
                                    :value="$isEditing ? old('title') : $track->title" />
                                <x-admin.form.file name="audio_file" :label="__('Audio fayl')" accept="audio/*" with-progress
                                    :currentName="__('Fayl mavjud')" :help="__('Yangi fayl yuklasangiz eskisi almashtiriladi')" />

                                <div class="flex justify-end gap-3 pt-2">
                                    <button type="button" @click="editId = null"
                                            class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">{{ __('Bekor qilish') }}</button>
                                    <button type="submit"
                                            class="bg-brand-500 hover:bg-brand-600 rounded-lg px-4 py-2.5 text-sm font-medium text-white">{{ __('Saqlash') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
