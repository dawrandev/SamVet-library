@php
    $subscription = $subscription ?? null;
    $editing = ! is_null($subscription);

    $curSubscriber = old('subscriber_id', $subscription?->subscriber_id);
    $curJournal = old('journal_id', $subscription?->journal_id);
    $curStartMonth = old('start_month', $subscription?->start_month?->value);
    $curEndMonth = old('end_month', $subscription?->end_month?->value);

    // Group publications by kind for <optgroup> (journals / newspapers)
    $journalsByKind = collect($journals)->groupBy(fn ($j) => $j->kind?->value ?? \App\Enums\PublicationKind::Journal->value);

    $base = 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90';
@endphp

<form
    method="POST"
    action="{{ $editing ? route('admin.subscriptions.update', $subscription) : route('admin.subscriptions.store') }}"
>
    @csrf
    @if ($editing) @method('PUT') @endif

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

    {{-- General error --}}
    @if ($errors->any())
        <x-alert type="error" class="mb-6">{{ __('Iltimos, formadagi xatolarni to‘g‘rilang.') }}</x-alert>
    @endif

    <div class="mx-auto max-w-2xl">
        <x-admin.form.section :title="__('Obuna ma’lumotlari')">
            <div class="space-y-5">
                {{-- Subscriber --}}
                <div>
                    <label for="subscriber_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Obunachi') }}<span class="text-error-500">*</span></label>
                    <select name="subscriber_id" id="subscriber_id" required
                            class="{{ $base }} {{ $errors->has('subscriber_id') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                        <option value="">{{ __('Tanlang') }}</option>
                        @foreach ($subscribers as $s)
                            <option value="{{ $s->id }}" @selected((string) $curSubscriber === (string) $s->id)>{{ $s->full_name }}</option>
                        @endforeach
                    </select>
                    @error('subscriber_id')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                </div>

                {{-- Publication (journal / newspaper) — grouped --}}
                <div>
                    <label for="journal_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Nashr') }}<span class="text-error-500">*</span></label>
                    <select name="journal_id" id="journal_id" required
                            class="{{ $base }} {{ $errors->has('journal_id') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                        <option value="">{{ __('Tanlang') }}</option>
                        @foreach ($journalsByKind as $kindValue => $group)
                            <optgroup label="{{ \App\Enums\PublicationKind::from($kindValue)->label() }}">
                                @foreach ($group as $j)
                                    <option value="{{ $j->id }}" @selected((string) $curJournal === (string) $j->id)>{{ $j->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('journal_id')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.form.input name="year" type="number" :label="__('Yil')" :value="$subscription?->year" required placeholder="{{ date('Y') }}" />
                    <x-admin.form.input name="amount" type="number" step="0.01" :label="__('Summa (so‘m)')" :value="$subscription?->amount" required placeholder="0.00" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    {{-- Start month --}}
                    <div>
                        <label for="start_month" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Boshlanish oyi') }}<span class="text-error-500">*</span></label>
                        <select name="start_month" id="start_month" required
                                class="{{ $base }} {{ $errors->has('start_month') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                            <option value="">{{ __('Tanlang') }}</option>
                            @foreach ($months as $m)
                                <option value="{{ $m->value }}" @selected((string) $curStartMonth === (string) $m->value)>{{ $m->label() }}</option>
                            @endforeach
                        </select>
                        @error('start_month')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- End month --}}
                    <div>
                        <label for="end_month" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Tugash oyi') }}<span class="text-error-500">*</span></label>
                        <select name="end_month" id="end_month" required
                                class="{{ $base }} {{ $errors->has('end_month') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                            <option value="">{{ __('Tanlang') }}</option>
                            @foreach ($months as $m)
                                <option value="{{ $m->value }}" @selected((string) $curEndMonth === (string) $m->value)>{{ $m->label() }}</option>
                            @endforeach
                        </select>
                        @error('end_month')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </x-admin.form.section>
    </div>
</form>
