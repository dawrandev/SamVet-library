@extends('layouts.admin')

@section('title', __('Obunalar'))

@section('content')
    @php
        // Journals grouped by kind for the modal's <optgroup> select.
        $journalsByKind = $journals->groupBy(fn ($j) => $j->kind?->value ?? 'journal');
    @endphp

    {{-- Create/edit happens in a modal (compact form). Alpine holds the state;
         on a validation error the page reloads and the modal re-opens from old input. --}}
    <div
        x-data="{
            open: {{ $errors->any() ? 'true' : 'false' }},
            editing: {{ old('subscription_id') ? 'true' : 'false' }},
            action: '{{ old('subscription_id') ? route('admin.subscriptions.update', old('subscription_id')) : route('admin.subscriptions.store') }}',
            form: {
                id: @js(old('subscription_id')),
                source: @js(old('source', 'reader')),
                reader_id: @js(old('reader_id', '')),
                journal_id: @js(old('journal_id', '')),
                delivery_location_id: @js(old('delivery_location_id', '')),
                post_branch_id: @js(old('post_branch_id', '')),
                year: @js(old('year', date('Y'))),
                start_month: @js(old('start_month', '1')),
                end_month: @js(old('end_month', '12')),
                amount: @js(old('amount', '')),
            },
            // Reference data for the reader/journal auto-fill panels below their pickers.
            readersData: @js($readers->map(fn ($r) => [
                'id' => (string) $r->id,
                'label' => $r->full_name,
                'isStudent' => $r->type->isStudent(),
                'place' => $r->affiliation_place,
                'unit' => $r->affiliation_unit,
                'group' => $r->affiliation_group,
            ])->values()),
            journalsData: @js($journals->map(fn ($j) => ['id' => (string) $j->id, 'index' => $j->index])->values()),
            // Shortlisted catalog entries by year — drives the year-aware journal
            // picker + auto price calculation from {{ \App\Models\Subscription::CATALOG_ENFORCED_FROM_YEAR }} on.
            catalogByYear: @js($catalogByYear),
            catalogEnforcedFromYear: {{ \App\Models\Subscription::CATALOG_ENFORCED_FROM_YEAR }},
            get selectedReader() {
                return this.readersData.find(r => r.id === this.form.reader_id) || null;
            },
            get selectedJournal() {
                return this.journalsData.find(j => j.id === this.form.journal_id) || null;
            },
            get isCatalogDriven() {
                return Number(this.form.year) >= this.catalogEnforcedFromYear;
            },
            get catalogOptions() {
                return this.catalogByYear[this.form.year] || [];
            },
            get selectedCatalogEntry() {
                return this.catalogOptions.find(c => c.journal_id === this.form.journal_id) || null;
            },
            get monthCount() {
                return Number(this.form.end_month) - Number(this.form.start_month) + 1;
            },
            get computedAmount() {
                return this.selectedCatalogEntry ? Math.round(this.selectedCatalogEntry.annual_price / 12 * this.monthCount) : null;
            },
            openCreate() {
                this.editing = false;
                this.action = '{{ route('admin.subscriptions.store') }}';
                this.form = { id: null, source: 'reader', reader_id: '', journal_id: '', delivery_location_id: '', post_branch_id: '', year: '{{ date('Y') }}', start_month: '1', end_month: '12', amount: '' };
                this.open = true;
            },
            openEdit(url, data) {
                this.editing = true;
                this.action = url;
                this.form = data;
                this.open = true;
            },
        }"
        @keydown.escape.window="open = false"
    >
        {{-- Title + New subscription --}}
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ __('Obunalar') }}</h2>
                <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">{{ __('Jami') }}: {{ $subscriptions->total() }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.subscription-catalog.index') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">
                    {{ __('Katalog') }}
                </a>
                <a href="{{ route('admin.subscriptions.dashboard') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">
                    {{ __('Tahlil') }}
                </a>
                <button type="button" @click="openCreate()"
                        class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                    <span class="text-lg leading-none">+</span> {{ __('Yangi obuna') }}
                </button>
            </div>
        </div>

        {{-- Success message --}}
        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        {{-- Total amount (report figure) --}}
        <div class="mb-5 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Jami obuna summasi') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90">{{ number_format($totalAmount, 0, '.', ' ') }} {{ __('so‘m') }}</p>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.subscriptions.index') }}"
              class="mb-5 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] sm:flex-row sm:items-end">
            <div class="flex-1">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Obunachi') }}</label>
                <select name="reader_id"
                        class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90">
                    <option value="">{{ __('Barchasi') }}</option>
                    @foreach ($readers as $r)
                        <option value="{{ $r->id }}" @selected(($filters['reader_id'] ?? null) == $r->id)>{{ $r->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Nashr') }}</label>
                <select name="journal_id"
                        class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90">
                    <option value="">{{ __('Barchasi') }}</option>
                    @foreach ($journals as $j)
                        <option value="{{ $j->id }}" @selected(($filters['journal_id'] ?? null) == $j->id)>{{ $j->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:w-32">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Yil') }}</label>
                <input type="number" name="year" value="{{ $filters['year'] ?? '' }}" placeholder="{{ date('Y') }}"
                       class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90" />
            </div>
            <div class="sm:w-44">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Manba') }}</label>
                <select name="source"
                        class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90">
                    <option value="">{{ __('Barchasi') }}</option>
                    @foreach ($sources as $s)
                        <option value="{{ $s->value }}" @selected(($filters['source'] ?? null) === $s->value)>{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 h-11 rounded-lg px-5 text-sm font-medium text-white transition">{{ __('Qidirish') }}</button>
                @if (array_filter($filters))
                    <a href="{{ route('admin.subscriptions.index') }}" class="flex h-11 items-center rounded-lg border border-gray-200 px-4 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400">{{ __('Tozalash') }}</a>
                @endif
            </div>
        </form>

        {{-- Table --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="max-w-full overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Manba') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Lavozimi') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Nashr') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Yil') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Davr') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Summa') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Manzili') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Pochta filiali') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Kvitansiya') }}</th>
                            <th class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Amallar') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($subscriptions as $subscription)
                            <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-4">
                                    @if ($subscription->source === \App\Enums\SubscriptionSource::Budget)
                                        <span class="text-theme-xs inline-flex items-center rounded-full bg-brand-50 px-2.5 py-1 font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">{{ __('Filial byudjetidan') }}</span>
                                    @else
                                        <span class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $subscription->reader?->full_name ?? '—' }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $subscription->reader?->affiliation_group ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    <p class="text-theme-sm text-gray-800 dark:text-white/90">{{ $subscription->journal?->name ?? '—' }}</p>
                                    <span class="text-theme-xs inline-flex rounded-full bg-gray-100 px-2 py-0.5 font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ $subscription->journal?->kind?->label() ?? '—' }}</span>
                                </td>
                                <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $subscription->year }}</td>
                                <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $subscription->start_month->label() }}–{{ $subscription->end_month->label() }}</td>
                                <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ number_format($subscription->amount, 0, '.', ' ') }} {{ __('so‘m') }}</td>
                                <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $subscription->deliveryLocation?->name ?? '—' }}</td>
                                <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $subscription->postBranch?->name ?? '—' }}</td>
                                <td class="px-5 py-4 text-theme-sm">
                                    @if ($subscription->receipt_file)
                                        <a href="{{ route('admin.subscriptions.receipt', $subscription) }}" target="_blank" rel="noopener noreferrer"
                                           class="font-medium text-brand-500 hover:text-brand-600">{{ __('Ko‘rish') }} 📎</a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button"
                                                @click="openEdit('{{ route('admin.subscriptions.update', $subscription) }}', { id: {{ $subscription->id }}, source: @js($subscription->source->value), reader_id: @js((string) $subscription->reader_id), journal_id: @js((string) $subscription->journal_id), delivery_location_id: @js((string) $subscription->delivery_location_id), post_branch_id: @js((string) $subscription->post_branch_id), year: @js((string) $subscription->year), start_month: @js((string) $subscription->start_month->value), end_month: @js((string) $subscription->end_month->value), amount: @js((string) $subscription->amount) })"
                                                class="text-theme-xs rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">{{ __('Tahrirlash') }}</button>
                                        <button type="button"
                                                @click="$store.confirm.ask('{{ route('admin.subscriptions.destroy', $subscription) }}', '{{ __('Obunani o‘chirishni tasdiqlaysizmi?') }}')"
                                                class="text-theme-xs rounded-lg border border-red-200 px-3 py-1.5 font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-5 py-12 text-center">
                                    <x-admin.icon name="clipboard-list" class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" />
                                    <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Obunalar topilmadi.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-5">
            {{ $subscriptions->links() }}
        </div>

        {{-- Create / edit modal --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-900/50" @click="open = false"></div>

            <div class="relative w-full max-w-xl rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                        <span x-show="!editing">{{ __('Yangi obuna') }}</span>
                        <span x-show="editing" x-cloak>{{ __('Obunani tahrirlash') }}</span>
                    </h3>
                    <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
                </div>

                <form method="POST" :action="action" enctype="multipart/form-data" class="space-y-4"
                      x-data="uploadForm" @submit="submitUpload($event)">
                    @csrf
                    <template x-if="editing"><input type="hidden" name="_method" value="PUT" /></template>
                    <input type="hidden" name="subscription_id" :value="form.id" />

                    <x-admin.form.upload-errors />
                    <x-admin.form.uploading-overlay />

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Manba') }}<span class="text-error-500">*</span></label>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach (\App\Enums\SubscriptionSource::cases() as $s)
                                <label class="flex h-11 cursor-pointer items-center justify-center rounded-lg border text-sm font-medium transition"
                                       x-bind:class="form.source === '{{ $s->value }}' ? 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'border-gray-300 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/5'">
                                    <input type="radio" name="source" value="{{ $s->value }}" x-model="form.source" class="sr-only" />
                                    {{ $s->label() }}
                                </label>
                            @endforeach
                        </div>
                        @error('source')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>

                    <div x-show="form.source === 'reader'" x-cloak>
                        <x-admin.form.searchable-select name="reader_id" :label="__('Obunachi')" x-model="form.reader_id"
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
                        <label for="m_journal" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Nashr') }}<span class="text-error-500">*</span></label>

                        {{-- Catalog-driven years: only the library's own shortlist for that year. --}}
                        <select x-show="isCatalogDriven" name="journal_id" x-model="form.journal_id" :required="isCatalogDriven"
                                class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 @error('journal_id') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror">
                            <option value="">{{ __('Tanlang') }}</option>
                            <template x-for="c in catalogOptions" :key="c.journal_id">
                                <option :value="c.journal_id" x-text="c.journal_name"></option>
                            </template>
                        </select>
                        <p x-show="isCatalogDriven && catalogOptions.length === 0" x-cloak class="mt-1.5 text-theme-xs text-warning-600 dark:text-warning-500">
                            {{ __('Bu yil uchun katalogda hech narsa yo‘q — avval qo‘shing:') }}
                            <a href="{{ route('admin.subscription-catalog.index') }}" class="font-medium underline">{{ __('Katalog') }}</a>
                        </p>

                        {{-- Legacy years — free choice, as before. --}}
                        <select x-show="!isCatalogDriven" id="m_journal" name="journal_id" x-model="form.journal_id" :required="!isCatalogDriven"
                                class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 @error('journal_id') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror">
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

                        <p x-show="!isCatalogDriven && selectedJournal?.index" x-cloak class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
                            {{ __('Indeks') }}: <span class="font-medium text-gray-700 dark:text-gray-300" x-text="selectedJournal?.index"></span>
                        </p>
                    </div>

                    <div>
                        <label for="m_delivery_location" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Yetkazib berish manzili') }}<span class="text-error-500">*</span></label>
                        <select name="delivery_location_id" id="m_delivery_location" x-model="form.delivery_location_id" required
                                class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 @error('delivery_location_id') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror">
                            <option value="">{{ __('Tanlang') }}</option>
                            @foreach ($deliveryLocations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                            @endforeach
                        </select>
                        @error('delivery_location_id')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                        <p class="mt-1.5 text-theme-xs text-gray-400">{{ __('Kutubxona/filial manzili — obunachining uy manzili emas.') }}</p>
                    </div>

                    <div>
                        <label for="m_post_branch" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Pochta filiali') }}</label>
                        <select name="post_branch_id" id="m_post_branch" x-model="form.post_branch_id"
                                class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 @error('post_branch_id') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror">
                            <option value="">{{ __('Tanlanmagan') }}</option>
                            @foreach ($postBranches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('post_branch_id')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="m_year" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Yil') }}<span class="text-error-500">*</span></label>
                            <input type="number" name="year" id="m_year" x-model="form.year" required min="2000" max="2100"
                                   class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 @error('year') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror" />
                            @error('year')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="m_start" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Boshlanish oyi') }}<span class="text-error-500">*</span></label>
                            <select name="start_month" id="m_start" x-model="form.start_month" required
                                    class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 @error('start_month') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror">
                                @foreach (\App\Enums\Month::cases() as $m)
                                    <option value="{{ $m->value }}">{{ $m->label() }}</option>
                                @endforeach
                            </select>
                            @error('start_month')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="m_end" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Tugash oyi') }}<span class="text-error-500">*</span></label>
                            <select name="end_month" id="m_end" x-model="form.end_month" required
                                    class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 @error('end_month') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror">
                                @foreach (\App\Enums\Month::cases() as $m)
                                    <option value="{{ $m->value }}">{{ $m->label() }}</option>
                                @endforeach
                            </select>
                            @error('end_month')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- Catalog-driven years: amount is always computed from the catalog, never typed. --}}
                    <div x-show="isCatalogDriven" x-cloak>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Obuna summasi (so‘m)') }}</label>
                        <p class="flex h-11 items-center rounded-lg border border-gray-200 bg-gray-50 px-4 text-sm font-medium text-gray-700 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-300"
                           x-text="selectedCatalogEntry ? (computedAmount.toLocaleString('ru-RU') + ' {{ __('so‘m') }} (' + monthCount + ' {{ __('oy') }})') : '{{ __('Avval nashrni tanlang') }}'"></p>
                        <p class="mt-1.5 text-theme-xs text-gray-400">{{ __('Katalogdagi yillik summadan avtomat hisoblanadi — qo‘lda o‘zgartirilmaydi.') }}</p>
                    </div>

                    {{-- Legacy years — manual amount, as before. --}}
                    <div x-show="!isCatalogDriven">
                        <label for="m_amount" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Obuna summasi (so‘m)') }}<span class="text-error-500">*</span></label>
                        <input type="number" name="amount" id="m_amount" x-model="form.amount" :required="!isCatalogDriven" min="0" step="1"
                               placeholder="{{ __('masalan: 150000') }}"
                               class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 @error('amount') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror" />
                        @error('amount')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>

                    <x-admin.form.file name="receipt_file" :label="__('Kvitansiya (rasm yoki PDF)')" accept="image/jpeg,image/png,image/jpg,application/pdf" with-progress
                        :help="__('JPG/PNG/PDF, 10 MB gacha')" />

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="open = false"
                                class="h-11 rounded-lg border border-gray-200 px-5 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">{{ __('Bekor qilish') }}</button>
                        <button type="submit"
                                class="bg-brand-500 hover:bg-brand-600 h-11 rounded-lg px-5 text-sm font-medium text-white transition">{{ __('Saqlash') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
