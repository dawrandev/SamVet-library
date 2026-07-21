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

    @include('pages.admin.articles.partials.fields')
</form>
