@php
    $subscription = $subscription ?? null;
    $editing = ! is_null($subscription);
    $journalsByKind = $journals->groupBy(fn ($j) => $j->kind?->value ?? 'journal');

    $curSource = old('source', $subscription?->source?->value ?? 'reader');
    $curReaderId = old('reader_id', (string) $subscription?->reader_id);
    $curJournalId = old('journal_id', (string) $subscription?->journal_id);

    $base = 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90';
@endphp

<div
    x-data="{
        source: @js((string) $curSource),
        readerId: @js((string) $curReaderId),
        journalId: @js((string) $curJournalId),
        readersData: @js($readers->map(fn ($r) => [
            'id' => (string) $r->id,
            'label' => $r->full_name,
            'isStudent' => $r->type->isStudent(),
            'place' => $r->affiliationPlace?->name,
            'unit' => $r->affiliationUnit?->name,
            'group' => $r->affiliationGroup?->name,
        ])->values()),
        journalsData: @js($journals->map(fn ($j) => ['id' => (string) $j->id, 'index' => $j->index])->values()),
        get selectedReader() {
            return this.readersData.find(r => r.id === this.readerId) || null;
        },
        get selectedJournal() {
            return this.journalsData.find(j => j.id === this.journalId) || null;
        },
    }"
>
    <form
        method="POST"
        action="{{ $editing ? route('admin.subscriptions.update', $subscription) : route('admin.subscriptions.store') }}"
        enctype="multipart/form-data"
        x-data="uploadForm"
        @submit="submitUpload($event)"
    >
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-admin.form.upload-errors />
        <x-admin.form.uploading-overlay />

        {{-- Header + actions (sticky) --}}
        <div class="sticky top-16 z-9 -mx-4 mb-6 flex items-center justify-between border-b border-gray-200 bg-gray-50/90 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 dark:border-gray-800 dark:bg-gray-900/90">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.subscriptions.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
                <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">
                    {{ $editing ? __('Obunani tahrirlash') : __('Yangi obuna') }}
                </h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.subscriptions.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</a>
                <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 rounded-lg px-5 py-2 text-sm font-medium text-white transition">{{ __('Saqlash') }}</button>
            </div>
        </div>

        <div class="mx-auto max-w-2xl space-y-5 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Manba') }}<span class="text-error-500">*</span></label>
                <div class="grid grid-cols-2 gap-3">
                    @foreach (\App\Enums\SubscriptionSource::cases() as $s)
                        <label class="flex h-11 cursor-pointer items-center justify-center rounded-lg border text-sm font-medium transition"
                               x-bind:class="source === '{{ $s->value }}' ? 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'border-gray-300 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/5'">
                            <input type="radio" name="source" value="{{ $s->value }}" x-model="source" class="sr-only" />
                            {{ $s->label() }}
                        </label>
                    @endforeach
                </div>
                @error('source')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
            </div>

            <div x-show="source === 'reader'" x-cloak>
                <x-admin.form.searchable-select name="reader_id" :label="__('Obunachi')" x-model="readerId"
                    :options="$readers->map(fn ($r) => ['id' => $r->id, 'label' => $r->full_name])" :required="true" />
                @error('reader_id')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror

                {{-- Auto-filled reference info — display only, not submitted. --}}
                <div x-show="selectedReader" x-cloak class="mt-2 grid grid-cols-3 gap-3 rounded-lg bg-gray-50 p-3 text-theme-xs dark:bg-white/5">
                    <div>
                        <dt class="text-gray-400" x-text="selectedReader?.isStudent ? '{{ __('O‘qish joyi') }}' : '{{ __('Ish joyi') }}'"></dt>
                        <dd class="mt-0.5 font-medium text-gray-700 dark:text-gray-300" x-text="selectedReader?.place || '—'"></dd>
                    </div>
                    <div>
                        <dt class="text-gray-400" x-text="selectedReader?.isStudent ? '{{ __('Mutaxassisligi') }}' : '{{ __('Bo‘limi') }}'"></dt>
                        <dd class="mt-0.5 font-medium text-gray-700 dark:text-gray-300" x-text="selectedReader?.unit || '—'"></dd>
                    </div>
                    <div>
                        <dt class="text-gray-400" x-text="selectedReader?.isStudent ? '{{ __('Guruhi') }}' : '{{ __('Lavozimi') }}'"></dt>
                        <dd class="mt-0.5 font-medium text-gray-700 dark:text-gray-300" x-text="selectedReader?.group || '—'"></dd>
                    </div>
                </div>
            </div>

            <div>
                <label for="journal_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Nashr') }}<span class="text-error-500">*</span></label>

                <select id="journal_id" name="journal_id" x-model="journalId" required
                        class="{{ $base }} @error('journal_id') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror">
                    <option value="">{{ __('Tanlang') }}</option>
                    @foreach (\App\Enums\PublicationKind::cases() as $kind)
                        @if (isset($journalsByKind[$kind->value]))
                            <optgroup label="{{ $kind->label() }}">
                                @foreach ($journalsByKind[$kind->value] as $j)
                                    <option value="{{ $j->id }}">{{ $j->name }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    @endforeach
                </select>
                @error('journal_id')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror

                <p x-show="selectedJournal?.index" x-cloak class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                    {{ __('Indeks') }}: <span class="font-medium text-gray-700 dark:text-gray-300" x-text="selectedJournal?.index"></span>
                </p>
            </div>

            <div>
                <label for="delivery_location_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Yetkazib berish manzili') }}<span class="text-error-500">*</span></label>
                <select name="delivery_location_id" id="delivery_location_id" required
                        class="{{ $base }} @error('delivery_location_id') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror">
                    <option value="">{{ __('Tanlang') }}</option>
                    @foreach ($deliveryLocations as $loc)
                        <option value="{{ $loc->id }}" @selected(old('delivery_location_id', $subscription?->delivery_location_id) == $loc->id)>{{ $loc->name }}</option>
                    @endforeach
                </select>
                @error('delivery_location_id')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                <p class="mt-1.5 text-theme-xs text-gray-400">{{ __('Kutubxona/filial manzili — obunachining uy manzili emas.') }}</p>
            </div>

            <div>
                <label for="post_branch_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Pochta filiali') }}</label>
                <select name="post_branch_id" id="post_branch_id"
                        class="{{ $base }} @error('post_branch_id') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror">
                    <option value="">{{ __('Tanlanmagan') }}</option>
                    @foreach ($postBranches as $branch)
                        <option value="{{ $branch->id }}" @selected(old('post_branch_id', $subscription?->post_branch_id) == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
                @error('post_branch_id')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="year" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Yil') }}<span class="text-error-500">*</span></label>
                    <input type="number" name="year" id="year" required min="2000" max="2100"
                           value="{{ old('year', $subscription?->year ?? date('Y')) }}"
                           class="{{ $base }} @error('year') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror" />
                    @error('year')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="start_month" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Boshlanish oyi') }}<span class="text-error-500">*</span></label>
                    <select name="start_month" id="start_month" required
                            class="{{ $base }} @error('start_month') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror">
                        @foreach (\App\Enums\Month::cases() as $m)
                            <option value="{{ $m->value }}" @selected((int) old('start_month', $subscription?->start_month?->value ?? 1) === $m->value)>{{ $m->label() }}</option>
                        @endforeach
                    </select>
                    @error('start_month')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="end_month" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Tugash oyi') }}<span class="text-error-500">*</span></label>
                    <select name="end_month" id="end_month" required
                            class="{{ $base }} @error('end_month') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror">
                        @foreach (\App\Enums\Month::cases() as $m)
                            <option value="{{ $m->value }}" @selected((int) old('end_month', $subscription?->end_month?->value ?? 12) === $m->value)>{{ $m->label() }}</option>
                        @endforeach
                    </select>
                    @error('end_month')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="amount" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Obuna summasi (so‘m)') }}<span class="text-error-500">*</span></label>
                <input type="number" name="amount" id="amount" required min="0" step="1"
                       value="{{ old('amount', $subscription?->amount) }}"
                       placeholder="{{ __('masalan: 150000') }}"
                       class="{{ $base }} @error('amount') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror" />
                @error('amount')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
            </div>

            <x-admin.form.file name="receipt_file" :label="__('Kvitansiya (rasm yoki PDF)')" accept="image/jpeg,image/png,image/jpg,application/pdf" with-progress
                :help="__('JPG/PNG/PDF, 10 MB gacha')" />
        </div>
    </form>
</div>
