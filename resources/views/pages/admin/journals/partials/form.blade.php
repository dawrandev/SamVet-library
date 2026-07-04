@php
    $journal = $journal ?? null;
    $editing = ! is_null($journal);

    $periodicityOptions = \App\Enums\JournalPeriodicity::cases();
    $currentPeriodicity = old('periodicity', $editing ? $journal->periodicity?->value : null);
@endphp

<form
    method="POST"
    action="{{ $editing ? route('admin.journals.update', $journal) : route('admin.journals.store') }}"
>
    @csrf
    @if ($editing) @method('PUT') @endif

    {{-- Sarlavha + amallar (sticky) --}}
    <div class="sticky top-16 z-9 -mx-4 mb-6 flex items-center justify-between border-b border-gray-200 bg-gray-50/90 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 dark:border-gray-800 dark:bg-gray-900/90">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.journals.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">
                {{ $editing ? __('Jurnalni tahrirlash') : __('Yangi jurnal') }}
            </h2>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.journals.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</a>
            <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 rounded-lg px-5 py-2 text-sm font-medium text-white transition">{{ __('Saqlash') }}</button>
        </div>
    </div>

    {{-- Umumiy xato --}}
    @if ($errors->any())
        <x-alert type="error" class="mb-6">{{ __('Iltimos, formadagi xatolarni to‘g‘rilang.') }}</x-alert>
    @endif

    {{-- Ikki bo'lim yonma-yon — kenglik to'liq ishlatiladi --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-admin.form.section :title="__('Asosiy ma’lumotlar')">
            <div class="space-y-5">
                <x-admin.form.input name="name" :label="__('Nomi')" :value="$journal?->name" required :placeholder="__('Jurnal nomi')" />

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.form.select name="journal_type_id" :label="__('Turi')" :options="$types" :selected="$journal?->journal_type_id" :placeholder="__('Tanlang')"
                        creatable create-translatable create-type="journal_type" :create-label="__('Yangi tur')" />
                    <x-admin.form.input name="founder" :label="__('Muassis')" :value="$journal?->founder" :placeholder="__('masalan: SamVMU')" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.form.select name="language_id" :label="__('Tili')" :options="$languages" :selected="$journal?->language_id" :placeholder="__('Tanlang')"
                        creatable create-translatable create-type="language" :create-label="__('Yangi til')" />
                    <x-admin.form.select name="publisher_id" :label="__('Nashriyoti')" :options="$publishers" :selected="$journal?->publisher_id" :placeholder="__('Tanlang')"
                        creatable create-type="publisher" :create-label="__('Yangi nashriyot')" />
                </div>

                <div>
                    <label for="periodicity" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Davriyligi') }}</label>
                    <select name="periodicity" id="periodicity"
                            class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 {{ $errors->has('periodicity') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                        <option value="">{{ __('Tanlang') }}</option>
                        @foreach ($periodicityOptions as $opt)
                            <option value="{{ $opt->value }}" @selected($currentPeriodicity === $opt->value)>{{ $opt->label() }}</option>
                        @endforeach
                    </select>
                    @error('periodicity')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                </div>
            </div>
        </x-admin.form.section>

        <x-admin.form.section :title="__('Kataloglashtirish')">
            <div class="space-y-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.form.input name="issn" :label="__('ISSN')" :value="$journal?->issn" :placeholder="__('masalan: 2181-1234')" />
                    <x-admin.form.input name="index" :label="__('Indeks')" :value="$journal?->index" :placeholder="__('indeks / raqam')"
                        :help="__('Client saytda ko‘rinmaydi.')" />
                </div>

                <x-admin.form.translatable-input name="publication_place" :label="__('Nashr joyi')"
                    :value="$editing ? $journal->getTranslations('publication_place') : []"
                    :placeholders="['uz' => 'masalan: Samarqand', 'ru' => 'например: Самарканд', 'kk' => 'mısalı: Samarqand']" />
            </div>
        </x-admin.form.section>
    </div>
</form>
