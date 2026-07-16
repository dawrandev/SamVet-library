@php
    $article = $article ?? null;
    $editing = ! is_null($article);

    // The controller resolves the pre-selected journal/issue (edit mode or redisplay
    // after a validation error) and passes them in — no DB query in the view.
    $selectedJournalId = $selectedJournalId ?? null;
    $selectedJournalName = $selectedJournalName ?? null;
    $selectedIssueId = $selectedIssueId ?? null;

    $categoryOptions = \App\Enums\ArticleCategory::cases();
    $currentCategory = old('category', $article?->category?->value);

    // The "Yangi maqola" vs "Yangi gazeta maqolasi" button already declared intent;
    // on edit, the article's actual parent journal decides which wording to use.
    $currentKind = $editing ? $article->journalIssue?->journal?->kind?->value : ($kind ?? null);
    $isNewspaperForm = $currentKind === \App\Enums\PublicationKind::Newspaper->value;
    $backParams = array_filter(['kind' => $currentKind]);
@endphp

<form
    method="POST"
    action="{{ $editing ? route('admin.articles.update', $article) : route('admin.articles.store') }}"
    enctype="multipart/form-data"
    @submit="submitUpload($event)"
    x-data="articleForm({
        searchUrl: '{{ route('admin.journals.search') }}',
        issuesUrlTemplate: '{{ route('admin.journals.issues.lookup', ['journal' => '__JID__']) }}',
        newJournalUrl: '{{ route('admin.journals.create', $backParams) }}',
        kind: {{ $editing ? 'null' : ($currentKind ? "'{$currentKind}'" : 'null') }},
        initial: {
            journalId: {{ $selectedJournalId ?? 'null' }},
            journalName: @js($selectedJournalName ?? ''),
            issueId: {{ $selectedIssueId ?? 'null' }},
            external: {{ $editing && $article->isExternal() ? 'true' : 'false' }},
        },
    })"
>
    @csrf
    @if ($editing) @method('PUT') @endif

    <x-admin.form.upload-errors />
    <x-admin.form.uploading-overlay />

    {{-- Keeps the chosen journal across a validation error (even when no issue was picked). --}}
    <input type="hidden" name="journal_id" x-model="journalId" />

    {{-- Header + actions (sticky) --}}
    <div class="sticky top-16 z-9 -mx-4 mb-6 flex items-center justify-between border-b border-gray-200 bg-gray-50/90 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 dark:border-gray-800 dark:bg-gray-900/90">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.articles.index', $backParams) }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">
                @if ($editing)
                    {{ $isNewspaperForm ? __('Gazeta maqolasini tahrirlash') : __('Maqolani tahrirlash') }}
                @else
                    {{ $isNewspaperForm ? __('Yangi gazeta maqolasi') : __('Yangi maqola') }}
                @endif
            </h2>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.articles.index', $backParams) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</a>
            <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 rounded-lg px-5 py-2 text-sm font-medium text-white transition">{{ __('Saqlash') }}</button>
        </div>
    </div>

    {{-- General error --}}
    @if ($errors->any())
        <x-alert type="error" class="mb-6">{{ __('Iltimos, formadagi xatolarni to‘g‘rilang.') }}</x-alert>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Left: journal & issue --}}
        <x-admin.form.section :title="$isNewspaperForm ? __('Gazeta va son') : __('Jurnal va son')">
            <div class="space-y-5">
                @unless ($isNewspaperForm)
                    {{-- A teacher's article in an international journal the library doesn't
                         hold — no journal/issue to link to, just a free-text journal name. --}}
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-400">
                        <input type="checkbox" x-model="external"
                               class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900" />
                        {{ __('Jurnal kutubxonada yo‘q (xalqaro/tashqi jurnalda chop etilgan)') }}
                    </label>
                @endunless

                <div x-show="! external">
                {{-- Journal autocomplete --}}
                <div class="relative" @click.outside="showResults = false">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        {{ $isNewspaperForm ? __('Gazeta') : __('Jurnal') }}<span class="text-error-500">*</span>
                    </label>
                    <input type="text" x-model="journalName" @input.debounce.300ms="search()" @focus="showResults = results.length > 0"
                           placeholder="{{ $isNewspaperForm ? __('Gazeta nomini yozing...') : __('Jurnal nomini yozing...') }}"
                           autocomplete="off"
                           class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />

                    {{-- Results dropdown --}}
                    <div x-show="showResults" x-cloak
                         class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900">
                        <template x-if="searching">
                            <div class="px-4 py-3 text-sm text-gray-400">{{ __('Qidirilmoqda...') }}</div>
                        </template>
                        <template x-for="j in results" :key="j.id">
                            <button type="button" @click="pickJournal(j)"
                                    class="flex w-full items-center justify-between gap-2 px-4 py-2.5 text-left text-sm hover:bg-gray-50 dark:hover:bg-white/5">
                                <span class="text-gray-800 dark:text-white/90" x-text="j.name"></span>
                                <span class="text-theme-xs text-gray-400" x-text="j.type ?? ''"></span>
                            </button>
                        </template>
                        <template x-if="! searching && results.length === 0 && journalName.trim() !== ''">
                            <div class="px-4 py-3 text-sm">
                                <p class="mb-2 text-gray-500 dark:text-gray-400">{{ $isNewspaperForm ? __('Gazeta topilmadi.') : __('Jurnal topilmadi.') }}</p>
                                <a :href="newJournalUrl"
                                   class="text-brand-500 hover:text-brand-600 font-medium">+ {{ $isNewspaperForm ? __('Yangi gazeta qo‘shish') : __('Yangi jurnal qo‘shish') }}</a>
                            </div>
                        </template>
                    </div>

                    @error('journal_issue_id')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    <p class="mt-1 text-theme-xs text-gray-400">{{ $isNewspaperForm ? __('Gazetani tanlagach, uning sonini tanlang.') : __('Jurnalni tanlagach, uning sonini tanlang.') }}</p>
                </div>

                {{-- Issue select (dependent on journal) --}}
                <div>
                    <label for="journal_issue_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        {{ __('Son') }}<span class="text-error-500">*</span>
                    </label>
                    <select name="journal_issue_id" id="journal_issue_id" x-ref="issueSelect" x-model="issueId" :required="! external"
                            :disabled="journalId === null || loadingIssues"
                            class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">{{ __('Avval jurnalni tanlang') }}</option>
                        <template x-for="i in issues" :key="i.id">
                            <option :value="i.id" x-text="i.issue_number + ' (' + i.year + ')'"></option>
                        </template>
                    </select>
                    <p x-show="loadingIssues" x-cloak class="mt-1 text-theme-xs text-gray-400">{{ __('Sonlar yuklanmoqda...') }}</p>
                    <p x-show="journalId !== null && ! loadingIssues && issues.length === 0" x-cloak class="mt-1 text-theme-xs text-error-500">
                        {{ __('Bu jurnalda son yo‘q. Avval son qo‘shing.') }}
                    </p>
                </div>
                </div>

                {{-- External journal (not held by the library) — free text. --}}
                <div x-show="external" x-cloak class="space-y-5">
                    <x-admin.form.input name="external_journal_name" :label="__('Jurnal nomi')" :value="$article?->external_journal_name"
                        :placeholder="__('masalan: Journal of Veterinary Science')" />
                    <x-admin.form.input name="external_journal_year" type="number" :label="__('Nashr yili')" :value="$article?->external_journal_year"
                        :placeholder="date('Y')" />
                    @error('external_journal_name')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                </div>
            </div>
        </x-admin.form.section>

        {{-- Right: article details --}}
        <x-admin.form.section :title="__('Maqola ma’lumotlari')">
            <div class="space-y-5">
                <x-admin.form.input name="title" :label="__('Sarlavha')" :value="$article?->title" required :placeholder="__('Maqola sarlavhasi')" />
                <x-admin.form.input name="author" :label="__('Muallif(lar)')" :value="$article?->author" required :placeholder="__('masalan: Aliyev A., Valiyev B.')"
                    :help="__('Bir nechta muallif vergul bilan ajratiladi.')" />

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.form.select name="resource_field_id" :label="__('Resurs sohasi')" :options="$resourceFields" :selected="$article?->resource_field_id" :placeholder="__('Tanlang')"
                        creatable create-translatable create-type="resource_field" :create-label="__('Yangi soha')" />
                    <x-admin.form.select name="language_id" :label="__('Tili')" :options="$languages" :selected="$article?->language_id" :placeholder="__('Tanlang')"
                        creatable create-translatable create-type="language" :create-label="__('Yangi til')" />
                </div>

                <div>
                    <label for="category" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Kategoriyasi') }}</label>
                    <select name="category" id="category"
                            class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 {{ $errors->has('category') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                        <option value="">{{ __('Tanlang') }}</option>
                        @foreach ($categoryOptions as $opt)
                            <option value="{{ $opt->value }}" @selected($currentCategory === $opt->value)>{{ $opt->label() }}</option>
                        @endforeach
                    </select>
                    @error('category')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.form.input name="doi" :label="__('DOI')" :value="$article?->doi" :placeholder="__('masalan: 10.1000/xyz')" />
                    <x-admin.form.input name="pages" :label="__('Sahifalar')" :value="$article?->pages" :placeholder="__('masalan: 45-52')" />
                </div>

                <x-admin.form.textarea name="annotation" :label="__('Annotatsiya')" :value="$article?->annotation" :rows="4" :placeholder="__('Maqola annotatsiyasi')" />

                <x-admin.form.file name="electronic_file" :label="__('Elektron fayl (PDF)')" accept="application/pdf" with-progress
                    :currentName="$article?->electronic_file ? __('Fayl mavjud') : null"
                    :help="$article?->electronic_file ? __('Yangi fayl yuklasangiz eskisi almashtiriladi') : __('PDF, 950 MB gacha')" />
            </div>
        </x-admin.form.section>
    </div>
</form>
