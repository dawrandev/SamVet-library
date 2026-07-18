@extends('layouts.admin')

@section('title', $dissertation->title)

@section('content')
    @php
        $journal = $dissertation->journalIssue?->journal;

        // Dissertation's own fields
        $details = array_filter([
            __('Muallifi') => $dissertation->author,
            __('Turi') => $dissertation->degree?->label(),
            __('Fan nomi') => $dissertation->scienceField?->name,
            __('Ixtisoslik shifri va nomi') => $dissertation->doctoralSpecialty?->name,
            __('Mutaxassislik shifri va nomi') => $dissertation->masterSpecialty?->name,
            __('Ilmiy rahbari') => $dissertation->advisor,
            __('Muassasi') => $dissertation->institution,
            __('Tili') => $dissertation->language?->name,
            __('Nashr joyi') => $dissertation->publicationPlace?->name,
            __('Himoya yili') => $dissertation->defense_year,
            __('Beti') => $dissertation->pages,
            __('UO‘K') => $dissertation->udc,
            __('Resurs sohasi') => $dissertation->resourceField?->name,
        ], fn ($v) => filled($v));

        // Admin-only fields — never surfaced on the client site.
        $adminDetails = array_filter([
            __('Inventari') => $dissertation->inventory_number,
            __('Holati') => $dissertation->condition?->label(),
        ], fn ($v) => filled($v));

        // Inherited meta (from the parent issue → journal — displayed, not stored)
        $inherited = array_filter([
            __('Jurnal nomi') => $journal?->name,
            __('Jurnal turi') => $journal?->type?->name,
            __('Nashriyoti') => $journal?->publisher,
            __('Nashriyot joyi') => $journal?->publicationPlace?->name,
            __('Yili') => $dissertation->journalIssue?->year,
            __('Soni') => $dissertation->journalIssue?->issue_number,
        ], fn ($v) => filled($v));
    @endphp

    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.dissertations.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ $dissertation->title }}</h2>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dissertations.edit', $dissertation) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Tahrirlash') }}</a>
            <button type="button"
                    @click="$store.confirm.ask('{{ route('admin.dissertations.destroy', $dissertation) }}', '{{ __('Dissertatsiyani o‘chirishni tasdiqlaysizmi?') }}')"
                    class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
        </div>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
    @endif

    <div class="grid grid-cols-12 gap-6">
        {{-- Left: dissertation details --}}
        <div class="col-span-12 space-y-6 xl:col-span-7">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Dissertatsiya ma’lumotlari') }}</h3>
                <dl class="space-y-3">
                    @foreach ($details as $label => $value)
                        <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-800/50">
                            <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                            <dd class="text-theme-sm text-right font-medium text-gray-800 dark:text-white/90">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($dissertation->annotation)
                    <div class="mt-5">
                        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Annotatsiya') }}</h4>
                        <p class="text-theme-sm whitespace-pre-line text-gray-600 dark:text-gray-400">{{ $dissertation->annotation }}</p>
                    </div>
                @endif
            </div>

            {{-- Admin-only fields (inventory/condition) — never shown on the client site. --}}
            @if ($adminDetails)
                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                    <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Inventar (faqat admin)') }}</h3>
                    <dl class="space-y-3">
                        @foreach ($adminDetails as $label => $value)
                            <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-800/50">
                                <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                                <dd class="text-theme-sm text-right font-medium text-gray-800 dark:text-white/90">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif

            {{-- Electronic file indicator --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Elektron fayl') }}</h3>
                @if ($dissertation->electronic_file)
                    <p class="text-theme-sm inline-flex items-center gap-2 text-success-600">
                        <span>📎</span> {{ __('PDF fayl biriktirilgan (himoyalangan).') }}
                    </p>
                @else
                    <p class="text-theme-sm text-gray-400">{{ __('Elektron fayl biriktirilmagan.') }}</p>
                @endif
            </div>
        </div>

        {{-- Right: inherited journal meta + location --}}
        <div class="col-span-12 space-y-6 xl:col-span-5">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Jurnal ma’lumotlari') }}</h3>
                <dl class="space-y-3">
                    @forelse ($inherited as $label => $value)
                        <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-800/50">
                            <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                            <dd class="text-theme-sm text-right font-medium text-gray-800 dark:text-white/90">
                                @if ($label === __('Jurnal nomi') && $journal)
                                    <a href="{{ route('admin.journals.show', $journal) }}" class="text-brand-600 hover:underline dark:text-brand-400">{{ $value }}</a>
                                @else
                                    {{ $value }}
                                @endif
                            </dd>
                        </div>
                    @empty
                        <p class="text-theme-sm text-gray-400">{{ __('Ma’lumot yo‘q') }}</p>
                    @endforelse
                </dl>
            </div>

            {{-- Location note --}}
            <div class="rounded-2xl border border-brand-200 bg-brand-50 p-5 dark:border-brand-500/30 dark:bg-brand-500/10 sm:p-6">
                <h3 class="mb-2 text-sm font-semibold text-brand-700 dark:text-brand-300">{{ __('Joylashuvi') }}</h3>
                <p class="text-theme-sm text-brand-700 dark:text-brand-300">
                    {{ __('Axborot resurs markazi Elektron o‘qish zalida joylashgan.') }}
                </p>
            </div>
        </div>
    </div>
@endsection
