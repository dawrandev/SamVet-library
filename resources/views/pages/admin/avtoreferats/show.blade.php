@extends('layouts.admin')

@section('title', $avtoreferat->title)

@section('content')
    @php
        // Avtoreferat's own fields
        $details = array_filter([
            __('Muallifi') => $avtoreferat->author,
            __('Ixtisoslik shifri va nomi') => $avtoreferat->specialty,
            __('Fan nomi') => $avtoreferat->scienceField?->name,
            __('Turi') => $avtoreferat->degree?->label(),
            __('Ilmiy rahbar') => $avtoreferat->advisor,
            __('Tayanch so‘zlar') => $avtoreferat->keywords,
        ], fn ($v) => filled($v));

        // Dissertation defense details
        $defense = array_filter([
            __('Ilmiy kengash raqami') => $avtoreferat->council_number,
            __('Dissertatsiya himoya muassasi') => $avtoreferat->defense_institution,
            __('Dissertatsiya bajarilgan muassasi') => $avtoreferat->performed_institution,
        ], fn ($v) => filled($v));

        // Bibliographic details
        $bibliographic = array_filter([
            __('UO‘K') => $avtoreferat->udc,
            __('Ro‘yxat raqami') => $avtoreferat->registration_number,
            __('Nashr joyi') => $avtoreferat->publicationPlace?->name,
            __('Himoya yili') => $avtoreferat->defense_year,
            __('Tillari') => $avtoreferat->languages->pluck('name')->implode(', ') ?: null,
        ], fn ($v) => filled($v));

        // Options for the copy form (Holati enum only — no format/status/location/lending, unlike BookCopy)
        $conditionOptions = \App\Enums\CopyCondition::cases();
        $conditionSelectOptions = collect($conditionOptions)->map(fn ($c) => ['id' => $c->value, 'label' => $c->label()]);

        // Flag to keep the modal open on server errors (Alpine initial state)
        $openStore = $errors->any() && old('_copy_form') === 'store';
        $openEditId = $errors->any() && old('_copy_form') === 'edit' ? (int) old('_copy_id') : null;
    @endphp

    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.avtoreferats.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ $avtoreferat->title }}</h2>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.avtoreferats.edit', $avtoreferat) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Tahrirlash') }}</a>
            <button type="button"
                    @click="$store.confirm.ask('{{ route('admin.avtoreferats.destroy', $avtoreferat) }}', '{{ __('Avtoreferatni o‘chirishni tasdiqlaysizmi?') }}')"
                    class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
        </div>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
    @endif

    <div class="grid grid-cols-12 gap-6">
        {{-- Left: avtoreferat details --}}
        <div class="col-span-12 space-y-6 xl:col-span-7">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Avtoreferat ma’lumotlari') }}</h3>
                <dl class="space-y-3">
                    @foreach ($details as $label => $value)
                        <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-800/50">
                            <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                            <dd class="text-theme-sm text-right font-medium text-gray-800 dark:text-white/90">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($avtoreferat->annotation)
                    <div class="mt-5">
                        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Annotatsiya') }}</h4>
                        <p class="text-theme-sm whitespace-pre-line text-gray-600 dark:text-gray-400">{{ $avtoreferat->annotation }}</p>
                    </div>
                @endif
            </div>

            {{-- Electronic file indicator --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Elektron fayl') }}</h3>
                @if ($avtoreferat->electronic_file)
                    <p class="text-theme-sm inline-flex items-center gap-2 text-success-600">
                        <span>📎</span> {{ __('PDF fayl biriktirilgan (himoyalangan).') }}
                    </p>
                @else
                    <p class="text-theme-sm text-gray-400">{{ __('Elektron fayl biriktirilmagan.') }}</p>
                @endif
            </div>
        </div>

        {{-- Right: defense details, bibliographic meta + location --}}
        <div class="col-span-12 space-y-6 xl:col-span-5">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Dissertatsiya himoyasi') }}</h3>
                <dl class="space-y-3">
                    @forelse ($defense as $label => $value)
                        <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-800/50">
                            <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                            <dd class="text-theme-sm text-right font-medium text-gray-800 dark:text-white/90">{{ $value }}</dd>
                        </div>
                    @empty
                        <p class="text-theme-sm text-gray-400">{{ __('Ma’lumot yo‘q') }}</p>
                    @endforelse
                </dl>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Bibliografik ma’lumotlar') }}</h3>
                <dl class="space-y-3">
                    @forelse ($bibliographic as $label => $value)
                        <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-800/50">
                            <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                            <dd class="text-theme-sm text-right font-medium text-gray-800 dark:text-white/90">{{ $value }}</dd>
                        </div>
                    @empty
                        <p class="text-theme-sm text-gray-400">{{ __('Ma’lumot yo‘q') }}</p>
                    @endforelse
                </dl>
            </div>
        </div>
    </div>

    {{-- Physical copies (librarian) — inventory-tracking only, no lending: an
         avtoreferat is still read only via its title-level electronic_file,
         never physically issued. --}}
    <div
        x-data="{
            showStore: {{ $openStore ? 'true' : 'false' }},
            editId: {{ $openEditId ?? 'null' }},
        }"
        class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
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
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Aktlar') }}</th>
                        <th class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Amallar') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($avtoreferat->copies as $copy)
                        <tr class="border-b border-gray-50 last:border-0 dark:border-gray-800/50">
                            <td class="px-5 py-3 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $copy->inventory_number }}</td>
                            <td class="px-5 py-3 text-theme-sm text-gray-600 dark:text-gray-400">{{ $copy->condition?->map(fn ($c) => $c->label())->join(', ') ?: '—' }}</td>
                            <td class="px-5 py-3 text-theme-xs">
                                <div class="{{ $copy->acquisition_act_number ? 'text-gray-600 dark:text-gray-400' : 'text-gray-400' }}">
                                    {{ __('Kirish') }}: {{ $copy->acquisition_act_number ?? '—' }}
                                    @if ($copy->acquisition_act_at) <span class="text-gray-400">({{ $copy->acquisition_act_at->format('d.m.Y') }})</span> @endif
                                </div>
                                <div class="{{ $copy->disposal_act_number ? 'text-gray-600 dark:text-gray-400' : 'text-gray-400' }}">
                                    {{ __('Chiqish') }}: {{ $copy->disposal_act_number ?? '—' }}
                                    @if ($copy->disposal_act_at) <span class="text-gray-400">({{ $copy->disposal_act_at->format('d.m.Y') }})</span> @endif
                                </div>
                            </td>
                            <td class="px-5 py-3 text-right text-theme-xs">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" @click="editId = {{ $copy->id }}"
                                            class="rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800">{{ __('Tahrirlash') }}</button>
                                    <button type="button"
                                            @click="$store.confirm.ask('{{ route('admin.avtoreferats.copies.destroy', [$avtoreferat, $copy]) }}', '{{ __('Nusxani o‘chirishni tasdiqlaysizmi?') }}')"
                                            class="rounded-lg border border-red-200 px-3 py-1.5 font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Nusxa yo‘q') }}</td>
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
                <form method="POST" action="{{ route('admin.avtoreferats.copies.store', $avtoreferat) }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="_copy_form" value="store" />

                    <x-admin.form.input name="inventory_number" :label="__('Inventar raqami')" required />

                    <x-admin.form.multiselect name="condition" :label="__('Holati')" :options="$conditionSelectOptions" />

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-admin.form.input name="acquisition_act_number" :label="__('Kirish akti raqami')" />
                        <x-admin.form.input name="acquisition_act_at" type="date" :label="__('Kirish akti sanasi')" />
                        <x-admin.form.input name="disposal_act_number" :label="__('Chiqish akti raqami')" />
                        <x-admin.form.input name="disposal_act_at" type="date" :label="__('Chiqish akti sanasi')" />
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
        @foreach ($avtoreferat->copies as $copy)
            <div x-show="editId === {{ $copy->id }}" x-cloak
                 class="fixed inset-0 z-99999 flex items-center justify-center p-4"
                 @keydown.escape.window="editId = null">
                <div class="fixed inset-0 bg-gray-900/50" @click="editId = null"></div>
                <div class="relative z-10 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
                    <div class="mb-5 flex items-center justify-between">
                        <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Nusxani tahrirlash') }}</h4>
                        <button type="button" @click="editId = null" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
                    </div>
                    @php $isEditing = $openEditId === $copy->id; @endphp
                    <form method="POST" action="{{ route('admin.avtoreferats.copies.update', [$avtoreferat, $copy]) }}" class="space-y-4">
                        @csrf @method('PUT')
                        <input type="hidden" name="_copy_form" value="edit" />
                        <input type="hidden" name="_copy_id" value="{{ $copy->id }}" />

                        <x-admin.form.input name="inventory_number" :label="__('Inventar raqami')" required
                            :value="$isEditing ? old('inventory_number') : $copy->inventory_number" />

                        <x-admin.form.multiselect name="condition" :label="__('Holati')" :options="$conditionSelectOptions"
                            :selected="$copy->condition?->map(fn ($c) => $c->value)->values()->all() ?? []" />

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <x-admin.form.input name="acquisition_act_number" :label="__('Kirish akti raqami')"
                                :value="$isEditing ? old('acquisition_act_number') : $copy->acquisition_act_number" />
                            <x-admin.form.input name="acquisition_act_at" type="date" :label="__('Kirish akti sanasi')"
                                :value="$isEditing ? old('acquisition_act_at') : $copy->acquisition_act_at?->format('Y-m-d')" />
                            <x-admin.form.input name="disposal_act_number" :label="__('Chiqish akti raqami')"
                                :value="$isEditing ? old('disposal_act_number') : $copy->disposal_act_number" />
                            <x-admin.form.input name="disposal_act_at" type="date" :label="__('Chiqish akti sanasi')"
                                :value="$isEditing ? old('disposal_act_at') : $copy->disposal_act_at?->format('Y-m-d')" />
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
@endsection
