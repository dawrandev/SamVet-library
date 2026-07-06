@extends('layouts.admin')

@section('title', $book->title)

@section('content')
    @php
        $hasPrint = $book->copies->contains(fn ($c) => $c->format->value === 'print');
        $hasBraille = $book->copies->contains(fn ($c) => $c->format->value === 'braille');
        $available = $book->copies->where('status.value', 'available')->count();
        $total = $book->copies->count();

        $formats = array_filter([
            $hasPrint ? __('Bosma') : null,
            $hasBraille ? __('Brayl') : null,
            $book->electronic_file ? __('Elektron') : null,
            $book->audio_file ? __('Audio') : null,
        ]);

        $meta = array_filter([
            __('Mualliflar') => $book->authors->pluck('name')->join(', '),
            __('Turi') => $book->type?->name,
            __('Tili') => $book->language?->name,
            __('Nashriyoti') => $book->publisher?->name,
            __('Nashr joyi') => $book->publication_place,
            __('Nashr yili') => $book->publication_year,
            __('Sahifalar soni') => $book->pages,
            __('ISBN') => $book->isbn,
            __('UO‘K') => $book->udc,
            __('Avtorlik belgi') => $book->author_mark,
            __('Tiraj') => $book->print_run,
        ], fn ($v) => filled($v));

        $statusColor = [
            'available' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
            'lost' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
            'written_off' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
        ];

        // Options for the copy form (enums + locations)
        $formatOptions = \App\Enums\BookFormat::cases();
        $conditionOptions = \App\Enums\CopyCondition::cases();
        $statusOptions = \App\Enums\CopyStatus::cases();
        $locations = \App\Models\Location::orderBy('name')->get();
    @endphp

    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.books.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ $book->title }}</h2>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.books.translations.create', $book) }}" class="rounded-lg border border-brand-200 px-4 py-2 text-sm font-medium text-brand-600 hover:bg-brand-50 dark:border-brand-500/30 dark:text-brand-400 dark:hover:bg-brand-500/10">+ {{ __('Tarjima qo‘shish') }}</a>
            <a href="{{ route('admin.books.edit', $book) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Tahrirlash') }}</a>
            <button type="button"
                    @click="$store.confirm.ask('{{ route('admin.books.destroy', $book) }}', '{{ __('Kitobni o‘chirishni tasdiqlaysizmi?') }}')"
                    class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6">
        {{-- Left: cover + formats + availability + files --}}
        <div class="col-span-12 space-y-6 xl:col-span-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="mx-auto flex h-56 w-40 items-center justify-center overflow-hidden rounded-xl bg-gray-100 text-5xl dark:bg-gray-800">
                    @if ($book->cover_image)
                        <img src="{{ asset('storage/' . $book->cover_image) }}" alt="" class="h-full w-full object-cover" />
                    @else
                        📕
                    @endif
                </div>

                {{-- Format badges --}}
                <div class="mt-4 flex flex-wrap justify-center gap-2">
                    @forelse ($formats as $f)
                        <span class="rounded-full bg-brand-50 px-3 py-1 text-theme-xs font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">{{ $f }}</span>
                    @empty
                        <span class="text-theme-xs text-gray-400">—</span>
                    @endforelse
                </div>

                {{-- Views + availability --}}
                <div class="mt-4 flex items-center justify-center gap-4 border-t border-gray-100 pt-4 text-sm dark:border-gray-800">
                    <span class="text-gray-500 dark:text-gray-400">👁️ {{ $book->views_count }} {{ __('Ko‘rishlar') }}</span>
                    <span class="rounded-full bg-success-50 px-2.5 py-0.5 text-theme-xs font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">{{ $available }}/{{ $total }} {{ __('mavjud') }}</span>
                </div>
            </div>

            {{-- Editions in other languages (work group) --}}
            @php
                $otherEditions = $book->work
                    ? $book->work->editions->where('id', '!=', $book->id)
                    : collect();
            @endphp
            @if ($otherEditions->isNotEmpty())
                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                    <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Boshqa tildagi nashrlar') }}</h3>
                    <ul class="space-y-2">
                        @foreach ($otherEditions as $edition)
                            <li>
                                <a href="{{ route('admin.books.show', $edition) }}"
                                   class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 px-3 py-2 text-sm hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.03]">
                                    <span class="font-medium text-gray-800 dark:text-white/90">{{ $edition->title }}</span>
                                    <span class="shrink-0 rounded-full bg-gray-100 px-2.5 py-0.5 text-theme-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">{{ $edition->language?->name ?? '—' }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Digital files --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Raqamli fayllar') }}</h3>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">{{ __('Elektron kitob (PDF)') }}</span>
                        <span class="{{ $book->electronic_file ? 'text-success-600' : 'text-gray-400' }}">{{ $book->electronic_file ? __('bor') : __('yo‘q') }}</span>
                    </li>
                    <li class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">{{ __('Audio (mp3)') }}</span>
                        <span class="{{ $book->audio_file ? 'text-success-600' : 'text-gray-400' }}">{{ $book->audio_file ? __('bor') : __('yo‘q') }}</span>
                    </li>
                </ul>
            </div>

            {{-- Categories --}}
            @if ($book->categories->isNotEmpty())
                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                    <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Kategoriyalar') }}</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($book->categories as $category)
                            <span class="rounded-lg border border-gray-200 px-2.5 py-1 text-theme-xs text-gray-600 dark:border-gray-700 dark:text-gray-400">
                                {{ $category->parent ? $category->parent->name . ' › ' : '' }}{{ $category->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Right: details + annotation + copies --}}
        <div class="col-span-12 space-y-6 xl:col-span-8">
            {{-- Bibliographic --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Kitob ma’lumotlari') }}</h3>
                <dl class="grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
                    @foreach ($meta as $label => $value)
                        <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-800/50">
                            <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                            <dd class="text-theme-sm text-right font-medium text-gray-800 dark:text-white/90">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            {{-- Annotation --}}
            @if ($book->annotation)
                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                    <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Annotatsiya') }}</h3>
                    <p class="text-theme-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ $book->annotation }}</p>
                </div>
            @endif

            {{-- Physical copies (librarian) --}}
            @php
                // Flag to keep the modal open on server errors (Alpine initial state)
                $openStore = $errors->any() && old('_copy_form') === 'store';
                $openEditId = $errors->any() && old('_copy_form') === 'edit' ? (int) old('_copy_id') : null;
            @endphp

            <div
                x-data="{
                    showStore: {{ $openStore ? 'true' : 'false' }},
                    editId: {{ $openEditId ?? 'null' }},
                }"
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
            >
                <div class="flex items-center justify-between border-b border-gray-100 p-5 dark:border-gray-800 sm:px-6">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Jismoniy nusxalar') }}</h3>
                    <button type="button" @click="showStore = true"
                            class="bg-brand-500 hover:bg-brand-600 inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium text-white">
                        + {{ __('Nusxa qo‘shish') }}
                    </button>
                </div>
                <div class="max-w-full overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Inventar raqami') }}</th>
                                <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Turi') }}</th>
                                <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Holati') }}</th>
                                <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Mavjudligi') }}</th>
                                <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Joylashuvi') }}</th>
                                <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Narxi') }}</th>
                                <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Aktlar') }}</th>
                                <th class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Amallar') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($book->copies as $copy)
                                <tr class="border-b border-gray-50 last:border-0 dark:border-gray-800/50">
                                    <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $copy->inventory_number }}</td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $copy->format->label() }}</td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $copy->condition->label() }}</td>
                                    <td class="px-5 py-3">
                                        <span class="rounded-full px-2.5 py-0.5 text-theme-xs font-medium {{ $statusColor[$copy->status->value] ?? '' }}">{{ $copy->status->label() }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $copy->location?->name ?? '—' }}</td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $copy->price ? number_format($copy->price, 0, '.', ' ') . ' ' . __('so‘m') : '—' }}</td>
                                    <td class="px-5 py-3 text-theme-xs">
                                        <span class="{{ $copy->acquisition_act ? 'text-success-600' : 'text-gray-400' }}">{{ __('Kirish') }}: {{ $copy->acquisition_act ? '📎' : '—' }}</span>
                                        <span class="ml-2 {{ $copy->disposal_act ? 'text-success-600' : 'text-gray-400' }}">{{ __('Chiqish') }}: {{ $copy->disposal_act ? '📎' : '—' }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-right text-theme-xs">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" @click="editId = {{ $copy->id }}"
                                                    class="rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800">{{ __('Tahrirlash') }}</button>
                                            <button type="button"
                                                    @click="$store.confirm.ask('{{ route('admin.books.copies.destroy', [$book, $copy]) }}', '{{ __('Nusxani o‘chirishni tasdiqlaysizmi?') }}')"
                                                    class="rounded-lg border border-red-200 px-3 py-1.5 font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-8 text-center text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Nusxa yo‘q') }}</td>
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
                            <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Yangi nusxa') }}</h4>
                            <button type="button" @click="showStore = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
                        </div>
                        <form method="POST" action="{{ route('admin.books.copies.store', $book) }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <input type="hidden" name="_copy_form" value="store" />

                            <x-admin.form.input name="inventory_number" :label="__('Inventar raqami')" required />

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Formati') }}<span class="text-error-500">*</span></label>
                                    <select name="format" required class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                        @foreach ($formatOptions as $opt)
                                            <option value="{{ $opt->value }}" @selected(old('format') === $opt->value)>{{ $opt->label() }}</option>
                                        @endforeach
                                    </select>
                                    @error('format')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Holati') }}<span class="text-error-500">*</span></label>
                                    <select name="condition" required class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                        @foreach ($conditionOptions as $opt)
                                            <option value="{{ $opt->value }}" @selected(old('condition') === $opt->value)>{{ $opt->label() }}</option>
                                        @endforeach
                                    </select>
                                    @error('condition')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Mavjudligi') }}<span class="text-error-500">*</span></label>
                                    <select name="status" required class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                        @foreach ($statusOptions as $opt)
                                            <option value="{{ $opt->value }}" @selected(old('status') === $opt->value)>{{ $opt->label() }}</option>
                                        @endforeach
                                    </select>
                                    @error('status')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Joylashuvi') }}</label>
                                    <select name="location_id" class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                        <option value="">{{ __('Tanlanmagan') }}</option>
                                        @foreach ($locations as $location)
                                            <option value="{{ $location->id }}" @selected((string) old('location_id') === (string) $location->id)>{{ $location->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('location_id')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <x-admin.form.input name="price" type="number" step="0.01" :label="__('Narxi (so‘m)')" />

                            <x-admin.form.file name="acquisition_act" :label="__('Kirish akti (PDF)')" accept="application/pdf" :help="__('Maks. 50 MB')" />
                            <x-admin.form.file name="disposal_act" :label="__('Chiqish akti (PDF)')" accept="application/pdf" :help="__('Maks. 50 MB')" />

                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" @click="showStore = false"
                                        class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">{{ __('Bekor qilish') }}</button>
                                <button type="submit"
                                        class="bg-brand-500 hover:bg-brand-600 rounded-lg px-4 py-2.5 text-sm font-medium text-white">{{ __('Saqlash') }}</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Edit modals (one per copy) --}}
                @foreach ($book->copies as $copy)
                    <div x-show="editId === {{ $copy->id }}" x-cloak
                         class="fixed inset-0 z-99999 flex items-center justify-center p-4"
                         @keydown.escape.window="editId = null">
                        <div class="fixed inset-0 bg-gray-900/50" @click="editId = null"></div>
                        <div class="relative z-10 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
                            <div class="mb-5 flex items-center justify-between">
                                <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Nusxani tahrirlash') }}</h4>
                                <button type="button" @click="editId = null" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
                            </div>
                            @php $isEditing = $openEditId === $copy->id; @endphp
                            <form method="POST" action="{{ route('admin.books.copies.update', [$book, $copy]) }}" enctype="multipart/form-data" class="space-y-4">
                                @csrf @method('PUT')
                                <input type="hidden" name="_copy_form" value="edit" />
                                <input type="hidden" name="_copy_id" value="{{ $copy->id }}" />

                                <x-admin.form.input name="inventory_number" :label="__('Inventar raqami')" required
                                    :value="$isEditing ? old('inventory_number') : $copy->inventory_number" />

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Formati') }}<span class="text-error-500">*</span></label>
                                        @php $curFormat = $isEditing ? old('format') : $copy->format->value; @endphp
                                        <select name="format" required class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                            @foreach ($formatOptions as $opt)
                                                <option value="{{ $opt->value }}" @selected($curFormat === $opt->value)>{{ $opt->label() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Holati') }}<span class="text-error-500">*</span></label>
                                        @php $curCondition = $isEditing ? old('condition') : $copy->condition->value; @endphp
                                        <select name="condition" required class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                            @foreach ($conditionOptions as $opt)
                                                <option value="{{ $opt->value }}" @selected($curCondition === $opt->value)>{{ $opt->label() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Mavjudligi') }}<span class="text-error-500">*</span></label>
                                        @php $curStatus = $isEditing ? old('status') : $copy->status->value; @endphp
                                        <select name="status" required class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                            @foreach ($statusOptions as $opt)
                                                <option value="{{ $opt->value }}" @selected($curStatus === $opt->value)>{{ $opt->label() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Joylashuvi') }}</label>
                                        @php $curLocation = $isEditing ? old('location_id') : $copy->location_id; @endphp
                                        <select name="location_id" class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                            <option value="">{{ __('Tanlanmagan') }}</option>
                                            @foreach ($locations as $location)
                                                <option value="{{ $location->id }}" @selected((string) $curLocation === (string) $location->id)>{{ $location->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <x-admin.form.input name="price" type="number" step="0.01" :label="__('Narxi (so‘m)')"
                                    :value="$isEditing ? old('price') : $copy->price" />

                                <x-admin.form.file name="acquisition_act" :label="__('Kirish akti (PDF)')" accept="application/pdf"
                                    :currentName="$copy->acquisition_act ? __('Fayl mavjud') : null"
                                    :help="$copy->acquisition_act ? __('Yangi fayl yuklasangiz eskisi almashtiriladi') : __('Maks. 50 MB')" />
                                <x-admin.form.file name="disposal_act" :label="__('Chiqish akti (PDF)')" accept="application/pdf"
                                    :currentName="$copy->disposal_act ? __('Fayl mavjud') : null"
                                    :help="$copy->disposal_act ? __('Yangi fayl yuklasangiz eskisi almashtiriladi') : __('Maks. 50 MB')" />

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
