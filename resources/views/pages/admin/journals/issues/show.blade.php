@extends('layouts.admin')

@section('title', $journal->name . ' — ' . $issue->issue_number)

@section('content')
    @php
        $conditionOptions = \App\Enums\CopyCondition::cases();
        $statusOptions = \App\Enums\CopyStatus::cases();

        $statusColor = [
            'available' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
            'borrowed' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500',
            'lost' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
            'written_off' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
        ];

        $openStore = $errors->any() && old('_copy_form') === 'store';
        $openEditId = $errors->any() && old('_copy_form') === 'edit' ? (int) old('_copy_id') : null;
    @endphp

    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.journals.show', $journal) }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ $journal->name }}</h2>
                <p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Soni') }}: {{ $issue->issue_number }} · {{ $issue->year }}</p>
            </div>
        </div>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
    @endif

    <div class="grid grid-cols-12 gap-6">
        {{-- Left: issue details --}}
        <div class="col-span-12 space-y-6 xl:col-span-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="mx-auto flex h-56 w-40 items-center justify-center overflow-hidden rounded-xl bg-gray-100 text-5xl dark:bg-gray-800">
                    @if ($issue->cover_image)
                        <img src="{{ asset('storage/' . $issue->cover_image) }}" alt="" class="h-full w-full object-cover" />
                    @else
                        <x-admin.icon name="newspaper" class="h-14 w-14 text-gray-300 dark:text-gray-600" />
                    @endif
                </div>
                <dl class="mt-5 space-y-3">
                    <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-800/50">
                        <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Nashr yili') }}</dt>
                        <dd class="text-theme-sm text-right font-medium text-gray-800 dark:text-white/90">{{ $issue->year }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-800/50">
                        <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Soni') }}</dt>
                        <dd class="text-theme-sm text-right font-medium text-gray-800 dark:text-white/90">{{ $issue->issue_number }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-800/50">
                        <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Betlar') }}</dt>
                        <dd class="text-theme-sm text-right font-medium text-gray-800 dark:text-white/90">{{ $issue->pages ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 pb-2">
                        <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Elektron fayl (PDF)') }}</dt>
                        <dd class="text-theme-sm text-right {{ $issue->electronic_file ? 'text-success-600' : 'text-gray-400' }}">{{ $issue->electronic_file ? __('bor') : __('yo‘q') }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Right: copies --}}
        <div class="col-span-12 space-y-6 xl:col-span-8">
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
                                <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Holati') }}</th>
                                <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Mavjudligi') }}</th>
                                <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Joylashuvi') }}</th>
                                <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Kelgan vaqti') }}</th>
                                <th class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Amallar') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($issue->copies as $copy)
                                <tr class="border-b border-gray-50 last:border-0 dark:border-gray-800/50">
                                    <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $copy->inventory_number }}</td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $copy->condition?->label() ?? '—' }}</td>
                                    <td class="px-5 py-3">
                                        <span class="rounded-full px-2.5 py-0.5 text-theme-xs font-medium {{ $statusColor[$copy->status->value] ?? '' }}">{{ $copy->status->label() }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $copy->location?->name ?? '—' }}</td>
                                    <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $copy->arrival_date?->format('d.m.Y') ?? '—' }}</td>
                                    <td class="px-5 py-3 text-right text-theme-xs">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" @click="editId = {{ $copy->id }}"
                                                    class="rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800">{{ __('Tahrirlash') }}</button>
                                            <button type="button"
                                                    @click="$store.confirm.ask('{{ route('admin.journal-issues.copies.destroy', [$issue, $copy]) }}', '{{ __('Nusxani o‘chirishni tasdiqlaysizmi?') }}')"
                                                    class="rounded-lg border border-red-200 px-3 py-1.5 font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Nusxa yo‘q') }}</td>
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
                        <form method="POST" action="{{ route('admin.journal-issues.copies.store', $issue) }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="_copy_form" value="store" />

                            <x-admin.form.input name="inventory_number" :label="__('Inventar raqami')" required />

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Holati') }}</label>
                                    <select name="condition" class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                        <option value="">{{ __('Tanlanmagan') }}</option>
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
                                <x-admin.form.input name="arrival_date" type="date" :label="__('Kelgan vaqti')" />
                            </div>

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
                @foreach ($issue->copies as $copy)
                    @php $isEditing = $openEditId === $copy->id; @endphp
                    <div x-show="editId === {{ $copy->id }}" x-cloak
                         class="fixed inset-0 z-99999 flex items-center justify-center p-4"
                         @keydown.escape.window="editId = null">
                        <div class="fixed inset-0 bg-gray-900/50" @click="editId = null"></div>
                        <div class="relative z-10 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
                            <div class="mb-5 flex items-center justify-between">
                                <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Nusxani tahrirlash') }}</h4>
                                <button type="button" @click="editId = null" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
                            </div>
                            <form method="POST" action="{{ route('admin.journal-issues.copies.update', [$issue, $copy]) }}" class="space-y-4">
                                @csrf @method('PUT')
                                <input type="hidden" name="_copy_form" value="edit" />
                                <input type="hidden" name="_copy_id" value="{{ $copy->id }}" />

                                <x-admin.form.input name="inventory_number" :label="__('Inventar raqami')" required
                                    :value="$isEditing ? old('inventory_number') : $copy->inventory_number" />

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Holati') }}</label>
                                        @php $curCondition = $isEditing ? old('condition') : $copy->condition?->value; @endphp
                                        <select name="condition" class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                            <option value="">{{ __('Tanlanmagan') }}</option>
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
                                    <x-admin.form.input name="arrival_date" type="date" :label="__('Kelgan vaqti')"
                                        :value="$isEditing ? old('arrival_date') : $copy->arrival_date?->format('Y-m-d')" />
                                </div>

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
