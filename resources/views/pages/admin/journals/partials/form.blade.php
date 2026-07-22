@php
    $journal = $journal ?? null;
    $editing = ! is_null($journal);

    $periodicityUnits = \App\Enums\PeriodicityUnit::cases();
    $newspaperTypeOptions = \App\Enums\NewspaperType::cases();
    $currentNewspaperType = old('newspaper_type', $editing ? $journal->newspaper_type?->value : null);
    $currentPeriodicityUnit = old('periodicity_unit', $editing ? $journal->periodicity_unit?->value : null);
    $currentPeriodicityInterval = old('periodicity_interval', $editing ? ($journal->periodicity_interval ?? 1) : 1);
    $currentPeriodicityCount = old('periodicity_count', $editing ? ($journal->periodicity_count ?? 1) : 1);

    $kindOptions = \App\Enums\PublicationKind::cases();
    $currentKind = old('kind', $editing ? $journal->kind?->value : ($kind ?? \App\Enums\PublicationKind::Journal->value));

    // "Maqola" is a third choice on the Turi selector, alongside Jurnal/Gazeta — but it
    // creates an Article (a different table entirely), not a Journal, so it's only
    // offered when creating fresh, never while editing an existing journal/newspaper.
    $currentEntryType = old('entry_type', $editing ? $currentKind : ($entryType ?? $currentKind));

    // Preserve the journal/newspaper list scope when navigating back/cancel.
    $backParams = array_filter(['kind' => $editing ? $journal->kind?->value : $currentKind]);

    // --- Article-branch context (fields.blade.php partial expects these) ---
    $article = null;
    $isNewspaperForm = false;
    $categoryOptions = \App\Enums\ArticleCategory::cases();
    $currentCategory = old('category');
@endphp

<form
    method="POST"
    :action="entryType === 'article' ? '{{ route('admin.articles.store') }}' : '{{ $editing ? route('admin.journals.update', $journal) : route('admin.journals.store') }}'"
    enctype="multipart/form-data"
    @submit="submitUpload($event)"
    x-data="{
        ...articleForm({
            searchUrl: '{{ route('admin.journals.search') }}',
            issuesUrlTemplate: '{{ route('admin.journals.issues.lookup', ['journal' => '__JID__']) }}',
            newJournalUrl: '{{ route('admin.journals.create') }}',
            initial: { journalId: null, journalName: '', issueId: null },
        }),
        entryType: '{{ $currentEntryType }}',
    }"
>
    @csrf
    @if ($editing) @method('PUT') @endif

    <x-admin.form.upload-errors />
    <x-admin.form.uploading-overlay />

    {{-- Keeps the chosen journal across a validation error, when entryType is "article". --}}
    <input type="hidden" name="journal_id" x-model="journalId" />

    {{-- Header + actions (sticky) --}}
    <div class="sticky top-16 z-9 -mx-4 mb-6 flex items-center justify-between border-b border-gray-200 bg-gray-50/90 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 dark:border-gray-800 dark:bg-gray-900/90">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.journals.index', $backParams) }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">
                @if ($editing)
                    {{ __('Davriy nashrni tahrirlash') }}
                @else
                    <span x-show="entryType !== 'article'">{{ __('Yangi davriy nashr') }}</span>
                    <span x-show="entryType === 'article'" x-cloak>{{ __('Yangi maqola') }}</span>
                @endif
            </h2>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.journals.index', $backParams) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</a>
            <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 rounded-lg px-5 py-2 text-sm font-medium text-white transition">{{ __('Saqlash') }}</button>
        </div>
    </div>

    {{-- General error --}}
    @if ($errors->any())
        <x-alert type="error" class="mb-6">{{ __('Iltimos, formadagi xatolarni to‘g‘rilang.') }}</x-alert>
    @endif

    {{-- Turi (jurnal / gazeta / maqola) — decides which whole form below is shown.
         Named "kind" directly: when entryType is "article" the value (kind=article)
         still gets submitted to admin.articles.store, but that endpoint doesn't
         validate/read a "kind" field at all, so it's simply ignored there. --}}
    <div class="mb-6 max-w-xs">
        <label for="kind" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Turi') }}<span class="text-error-500">*</span></label>
        <select name="kind" id="kind" x-model="entryType"
                class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            @foreach ($kindOptions as $opt)
                <option value="{{ $opt->value }}" @selected($currentEntryType === $opt->value)>{{ $opt->label() }}</option>
            @endforeach
            @unless ($editing)
                <option value="article" @selected($currentEntryType === 'article')>{{ __('Maqola') }}</option>
            @endunless
        </select>
        <p class="mt-1 text-theme-xs text-gray-400">{{ __('Maqola tanlansa — mavjud jurnal/gazeta soniga bog‘langan holda maqola qo‘shiladi.') }}</p>
    </div>

    {{-- Journal/newspaper fields --}}
    <template x-if="entryType !== 'article'">
        <div>
            {{-- Two sections side by side — full width used --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-admin.form.section :title="__('Asosiy ma’lumotlar')">
                    <div class="space-y-5">
                        <x-admin.form.input name="name" :label="__('Nomi')" :value="$journal?->name" required :placeholder="__('Jurnal nomi')" />

                        <div class="grid gap-5 sm:grid-cols-2">
                            {{-- Newspapers use a fixed, closed set of 2 types (NewspaperType enum) —
                                 not the open, admin-extendable journal_type_id lookup that journals use. --}}
                            <div x-show="entryType === '{{ \App\Enums\PublicationKind::Newspaper->value }}'" x-cloak>
                                <label for="newspaper_type" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Turi') }}</label>
                                <select name="newspaper_type" id="newspaper_type"
                                        class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 {{ $errors->has('newspaper_type') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                                    <option value="">{{ __('Tanlang') }}</option>
                                    @foreach ($newspaperTypeOptions as $opt)
                                        <option value="{{ $opt->value }}" @selected($currentNewspaperType === $opt->value)>{{ $opt->label() }}</option>
                                    @endforeach
                                </select>
                                @error('newspaper_type')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                            </div>
                            <div x-show="entryType !== '{{ \App\Enums\PublicationKind::Newspaper->value }}'">
                                <x-admin.form.select name="journal_type_id" :label="__('Turi')" :options="$types" :selected="$journal?->journal_type_id" :placeholder="__('Tanlang')"
                                    creatable create-translatable create-type="journal_type" :create-label="__('Yangi tur')" />
                            </div>
                            <x-admin.form.input name="founder" :label="__('Muassislar')" :value="$journal?->founder" :placeholder="__('masalan: SamVMU')" />
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-admin.form.select name="language_id" :label="__('Tili')" :options="$languages" :selected="$journal?->language_id" :placeholder="__('Tanlang')"
                                creatable create-translatable create-type="language" :create-label="__('Yangi til')" />
                            <x-admin.form.select name="publication_place_id" :label="__('Nashr joyi')" :options="$publicationPlaces" :selected="$journal?->publication_place_id" :placeholder="__('Tanlang')"
                                creatable create-translatable create-type="publication_place" :create-label="__('Yangi nashriyot joyi')" />
                        </div>

                        <div x-data="{ unit: '{{ $currentPeriodicityUnit }}' }">
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Davriylik') }}</label>
                            <div class="flex flex-wrap items-center gap-2">
                                <input type="number" name="periodicity_interval" min="1" max="60"
                                       x-show="unit !== '' && unit !== '{{ \App\Enums\PeriodicityUnit::Irregular->value }}'"
                                       value="{{ $currentPeriodicityInterval }}"
                                       class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-20 rounded-lg border border-gray-300 bg-transparent px-3 text-center text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />

                                <select name="periodicity_unit" x-model="unit"
                                        class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 {{ $errors->has('periodicity_unit') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                                    <option value="">{{ __('Tanlang') }}</option>
                                    @foreach ($periodicityUnits as $opt)
                                        <option value="{{ $opt->value }}" @selected($currentPeriodicityUnit === $opt->value)>{{ $opt->label() }}</option>
                                    @endforeach
                                </select>

                                <span x-show="unit !== '' && unit !== '{{ \App\Enums\PeriodicityUnit::Irregular->value }}'" class="text-sm text-gray-500 dark:text-gray-400">{{ __('da') }}</span>

                                <input type="number" name="periodicity_count" min="1" max="60"
                                       x-show="unit !== '' && unit !== '{{ \App\Enums\PeriodicityUnit::Irregular->value }}'"
                                       value="{{ $currentPeriodicityCount }}"
                                       class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-20 rounded-lg border border-gray-300 bg-transparent px-3 text-center text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />

                                <span x-show="unit !== '' && unit !== '{{ \App\Enums\PeriodicityUnit::Irregular->value }}'" class="text-sm text-gray-500 dark:text-gray-400">{{ __('marta') }}</span>
                            </div>
                            <p class="mt-1.5 text-theme-xs text-gray-400">{{ __('Masalan: 2, Hafta, 3 → “2 haftada 3 marta”') }}</p>
                            @error('periodicity_unit')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                            @error('periodicity_interval')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                            @error('periodicity_count')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </x-admin.form.section>

                <x-admin.form.section :title="__('Qo‘shimcha ma’lumotlar')">
                    <div class="space-y-5">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-admin.form.input name="issn" :label="__('ISSN')" :value="$journal?->issn" :placeholder="__('masalan: 2181-1234')" />
                            <x-admin.form.input name="index" :label="__('Indeks')" :value="$journal?->index" :placeholder="__('indeks / raqam')"
                                :help="__('Ochiq saytda ko‘rinmaydi.')" />
                        </div>
                    </div>
                </x-admin.form.section>
            </div>
        </div>
    </template>

    {{-- Article fields — shown when "Maqola" is picked. Submits to admin.articles.store. --}}
    <template x-if="entryType === 'article'">
        @include('pages.admin.articles.partials.fields')
    </template>
</form>
