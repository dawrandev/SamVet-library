@extends('layouts.admin')

@section('title', $journal->name)

@section('content')
    @php
        // Flag to keep the issue form open on server errors
        $openStore = $errors->any() && old('_issue_form') === 'store';
        $openEditId = $errors->any() && old('_issue_form') === 'edit' ? (int) old('_issue_id') : null;
    @endphp

    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.journals.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ $journal->name }}</h2>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.journals.edit', $journal) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Tahrirlash') }}</a>
            <button type="button"
                    @click="$store.confirm.ask('{{ route('admin.journals.destroy', $journal) }}', '{{ __('Jurnalni o‘chirishni tasdiqlaysizmi?') }}')"
                    class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
        </div>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
    @endif

    <div class="grid grid-cols-12 gap-6">
        {{-- Left: journal details --}}
        <div class="col-span-12 space-y-6 xl:col-span-4">
            <x-admin.journal-info-panel :journal="$journal" class="sm:p-6" />
        </div>

        {{-- Right: issues --}}
        <div class="col-span-12 space-y-6 xl:col-span-8">
            <div
                x-data="{
                    showStore: {{ $openStore ? 'true' : 'false' }},
                    editId: {{ $openEditId ?? 'null' }},
                }"
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
            >
                <div class="flex items-center justify-between border-b border-gray-100 p-5 dark:border-gray-800 sm:px-6">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Sonlar') }}</h3>
                    <button type="button" @click="showStore = true"
                            class="bg-brand-500 hover:bg-brand-600 inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium text-white">
                        + {{ __('Son qo‘shish') }}
                    </button>
                </div>
                <div class="max-w-full overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Yili') }}</th>
                                <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Soni') }}</th>
                                <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Betlar') }}</th>
                                <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Nusxalar') }}</th>
                                <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('PDF') }}</th>
                                <th class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Amallar') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($journal->issues as $issue)
                                <tr class="border-b border-gray-50 last:border-0 dark:border-gray-800/50">
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $issue->year }}</td>
                                    <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">
                                        <a href="{{ route('admin.journals.issues.show', [$journal, $issue]) }}" class="text-brand-600 hover:underline dark:text-brand-400">{{ $issue->issue_number }}</a>
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $issue->pages ?? '—' }}</td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $issue->copies_count ?? $issue->copies->count() }}</td>
                                    <td class="px-5 py-3 text-theme-xs">
                                        <span class="{{ $issue->electronic_file ? 'text-success-600' : 'text-gray-400' }}">{{ $issue->electronic_file ? '📎' : '—' }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-right text-theme-xs">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('admin.journals.issues.show', [$journal, $issue]) }}"
                                               class="rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800">{{ __('Ko‘rish') }}</a>
                                            <button type="button" @click="editId = {{ $issue->id }}"
                                                    class="rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800">{{ __('Tahrirlash') }}</button>
                                            <button type="button"
                                                    @click="$store.confirm.ask('{{ route('admin.journals.issues.destroy', [$journal, $issue]) }}', '{{ __('Sonni o‘chirishni tasdiqlaysizmi?') }}')"
                                                    class="rounded-lg border border-red-200 px-3 py-1.5 font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Son yo‘q') }}</td>
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
                            <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Yangi son') }}</h4>
                            <button type="button" @click="showStore = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
                        </div>
                        <form method="POST" action="{{ route('admin.journals.issues.store', $journal) }}" enctype="multipart/form-data" class="space-y-4"
                              x-data="uploadForm" @submit="submitUpload($event)">
                            @csrf
                            <input type="hidden" name="_issue_form" value="store" />
                            <x-admin.form.upload-errors />
                            <x-admin.form.uploading-overlay />

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <x-admin.form.input name="year" type="number" :label="__('Nashr yili')" required placeholder="{{ date('Y') }}" />
                                <x-admin.form.input name="issue_number" :label="__('Soni')" required :placeholder="__('masalan: 2024/3')" />
                            </div>
                            <x-admin.form.input name="issue_date" type="date" :label="__('Kelgan vaqti')" />
                            <x-admin.form.input name="pages" type="number" :label="__('Sahifalar soni')" />
                            <x-admin.form.file name="cover" :label="__('Muqova rasmi')" :image="true" accept="image/*" with-progress :help="__('JPG/PNG, 2 MB gacha')" />
                            <x-admin.form.file name="electronic_file" :label="__('Elektron fayl (PDF)')" accept="application/pdf" with-progress :help="__('PDF, 950 MB gacha')" />

                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" @click="showStore = false"
                                        class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">{{ __('Bekor qilish') }}</button>
                                <button type="submit"
                                        class="bg-brand-500 hover:bg-brand-600 rounded-lg px-4 py-2.5 text-sm font-medium text-white">{{ __('Saqlash') }}</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Edit modals (one per issue) --}}
                @foreach ($journal->issues as $issue)
                    @php $isEditing = $openEditId === $issue->id; @endphp
                    <div x-show="editId === {{ $issue->id }}" x-cloak
                         class="fixed inset-0 z-99999 flex items-center justify-center p-4"
                         @keydown.escape.window="editId = null">
                        <div class="fixed inset-0 bg-gray-900/50" @click="editId = null"></div>
                        <div class="relative z-10 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
                            <div class="mb-5 flex items-center justify-between">
                                <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Sonni tahrirlash') }}</h4>
                                <button type="button" @click="editId = null" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
                            </div>
                            <form method="POST" action="{{ route('admin.journals.issues.update', [$journal, $issue]) }}" enctype="multipart/form-data" class="space-y-4"
                                  x-data="uploadForm" @submit="submitUpload($event)">
                                @csrf @method('PUT')
                                <input type="hidden" name="_issue_form" value="edit" />
                                <input type="hidden" name="_issue_id" value="{{ $issue->id }}" />
                                <x-admin.form.upload-errors />
                                <x-admin.form.uploading-overlay />

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <x-admin.form.input name="year" type="number" :label="__('Nashr yili')" required
                                        :value="$isEditing ? old('year') : $issue->year" />
                                    <x-admin.form.input name="issue_number" :label="__('Soni')" required
                                        :value="$isEditing ? old('issue_number') : $issue->issue_number" />
                                </div>
                                <x-admin.form.input name="issue_date" type="date" :label="__('Kelgan vaqti')"
                                    :value="$isEditing ? old('issue_date') : $issue->issue_date?->format('Y-m-d')" />
                                <x-admin.form.input name="pages" type="number" :label="__('Sahifalar soni')"
                                    :value="$isEditing ? old('pages') : $issue->pages" />
                                <x-admin.form.file name="cover" :label="__('Muqova rasmi')" :image="true" accept="image/*" with-progress
                                    :currentUrl="$issue->cover_image ? asset('storage/' . $issue->cover_image) : null"
                                    :help="$issue->cover_image ? __('Yangi fayl yuklasangiz eskisi almashtiriladi') : __('JPG/PNG, 2 MB gacha')" />
                                <x-admin.form.file name="electronic_file" :label="__('Elektron fayl (PDF)')" accept="application/pdf" with-progress
                                    :currentName="$issue->electronic_file ? __('Fayl mavjud') : null"
                                    :help="$issue->electronic_file ? __('Yangi fayl yuklasangiz eskisi almashtiriladi') : __('PDF, 950 MB gacha')" />

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
