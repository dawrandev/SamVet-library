@php
    $journal = $journal ?? null;
    $editing = ! is_null($journal);

    $periodicityOptions = \App\Enums\JournalPeriodicity::cases();
    $newspaperTypeOptions = \App\Enums\NewspaperType::cases();
    $currentNewspaperType = old('newspaper_type', $editing ? $journal->newspaper_type?->value : null);
    $currentPeriodicity = old('periodicity', $editing ? $journal->periodicity?->value : null);

    $kindOptions = \App\Enums\PublicationKind::cases();
    $currentKind = old('kind', $editing ? $journal->kind?->value : ($kind ?? \App\Enums\PublicationKind::Journal->value));

    // Preserve the journal/newspaper list scope when navigating back/cancel.
    $backParams = array_filter(['kind' => $editing ? $journal->kind?->value : $currentKind]);
@endphp

<form
    method="POST"
    action="{{ $editing ? route('admin.journals.update', $journal) : route('admin.journals.store') }}"
    x-data="{ kind: '{{ $currentKind }}' }"
>
    @csrf
    @if ($editing) @method('PUT') @endif

    {{-- Header + actions (sticky) --}}
    <div class="sticky top-16 z-9 -mx-4 mb-6 flex items-center justify-between border-b border-gray-200 bg-gray-50/90 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 dark:border-gray-800 dark:bg-gray-900/90">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.journals.index', $backParams) }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">
                {{ $editing ? __('Davriy nashrni tahrirlash') : __('Yangi davriy nashr') }}
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

    {{-- Two sections side by side — full width used --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-admin.form.section :title="__('Asosiy ma’lumotlar')">
            <div class="space-y-5">
                <x-admin.form.input name="name" :label="__('Nomi')" :value="$journal?->name" required :placeholder="__('Jurnal nomi')" />

                {{-- Publication kind (journal / newspaper) — decides whether copies of this
                     periodical carry an inventory number (journal) or not (newspaper). --}}
                <div>
                    <label for="kind" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Turi (jurnal/gazeta)') }}<span class="text-error-500">*</span></label>
                    <select name="kind" id="kind" required x-model="kind"
                            class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 {{ $errors->has('kind') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                        @foreach ($kindOptions as $opt)
                            <option value="{{ $opt->value }}" @selected($currentKind === $opt->value)>{{ $opt->label() }}</option>
                        @endforeach
                    </select>
                    @error('kind')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    {{-- Newspapers use a fixed, closed set of 2 types (NewspaperType enum) —
                         not the open, admin-extendable journal_type_id lookup that journals use. --}}
                    <div x-show="kind === '{{ \App\Enums\PublicationKind::Newspaper->value }}'" x-cloak>
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
                    <div x-show="kind !== '{{ \App\Enums\PublicationKind::Newspaper->value }}'">
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

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="periodicity" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Davriylik birligi') }}</label>
                        <select name="periodicity" id="periodicity"
                                class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 {{ $errors->has('periodicity') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                            <option value="">{{ __('Tanlang') }}</option>
                            @foreach ($periodicityOptions as $opt)
                                <option value="{{ $opt->value }}" @selected($currentPeriodicity === $opt->value)>{{ $opt->label() }}</option>
                            @endforeach
                        </select>
                        @error('periodicity')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>
                    <x-admin.form.input name="periodicity_count" type="number" min="1" max="31" :label="__('Necha marta')"
                        :value="$journal?->periodicity_count" :placeholder="__('masalan: 3')"
                        :help="__('masalan: haftada 3 marta, oyda 2 marta')" />
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

                <x-admin.form.input name="publisher" :label="__('Nashriyoti')"
                    :value="old('publisher', $journal?->publisher)" placeholder="{{ __('masalan: Samarqand') }}" />
            </div>
        </x-admin.form.section>
    </div>
</form>
