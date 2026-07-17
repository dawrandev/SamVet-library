@extends('layouts.admin')

@section('title', $reader->full_name)

@section('content')
    @php
        $isStudent = $reader->type->isStudent();

        $statusColor = [
            'active' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
            'blocked' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
            'left' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
            'suspended' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500',
        ];

        // Affiliation labels — depending on student/staff
        $affiliation = array_filter([
            ($isStudent ? __('O‘qish joyi') : __('Ish joyi')) => $reader->affiliation_place,
            ($isStudent ? __('Mutaxassisligi') : __('Bo‘limi')) => $reader->affiliation_unit,
            ($isStudent ? __('Guruhi') : __('Lavozimi')) => $reader->affiliation_group,
        ], fn ($v) => filled($v));

        $personal = array_filter([
            __('Millati') => $reader->nationality,
            __('Tug‘ilgan sana') => $reader->birth_date?->format('d.m.Y'),
            __('Jinsi') => $reader->gender?->label(),
            __('Passport') => $reader->passport,
            __('JSHSHIR (PINFL)') => $reader->pinfl,
            __('Tuman') => $reader->district,
            __('Manzil') => $reader->address,
            __('Telefon') => $reader->phone,
            __('A‘zolik yili') => $reader->member_year,
        ], fn ($v) => filled($v));

        $additional = array_filter([
            __('ID raqami') => $reader->id_number,
            __('Ro‘yxat raqami') => $reader->registration_number,
            __('Berilgan sana') => $reader->issued_date?->format('d.m.Y'),
            __('Boshqa kutubxona a‘zosi') => $reader->other_library_member,
        ], fn ($v) => filled($v));
    @endphp

    @php
        $isActiveOrSuspended = in_array($reader->status, [\App\Enums\ReaderStatus::Active, \App\Enums\ReaderStatus::Suspended], true);
        $warningCount = $reader->warnings->count();
        $outstandingLoans = $reader->activeLoans()->with(['loanable' => function ($morphTo) {
            $morphTo->morphWith([
                \App\Models\BookCopy::class => ['book'],
                \App\Models\JournalCopy::class => ['issue.journal'],
            ]);
        }])->get();
    @endphp

    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
         x-data="{
             blockOpen: {{ $errors->has('blocked_until') || $errors->has('block_reason') ? 'true' : 'false' }},
             finishOpen: {{ $errors->has('left_reason') ? 'true' : 'false' }},
             deleteOpen: false,
         }">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.readers.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ $reader->full_name }}</h2>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.readers.edit', $reader) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Tahrirlash') }}</a>

            @if ($isActiveOrSuspended)
                {{-- Block (modal: permanent or until date + reason) --}}
                <button type="button" @click="blockOpen = true"
                        class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('Bloklash') }}</button>

                {{-- Finish usage (graduated / left employment) — blocked while books are outstanding --}}
                <button type="button" @click="finishOpen = true"
                        class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Foydalanishni tugatish') }}</button>
            @else
                {{-- Restore (blocked / left) --}}
                <button type="button"
                        @click="$store.confirm.ask('{{ route('admin.readers.restore', $reader) }}', '{{ __('Foydalanuvchini tiklashni tasdiqlaysizmi?') }}', 'PATCH')"
                        class="rounded-lg border border-success-200 px-4 py-2 text-sm font-medium text-success-600 hover:bg-success-50 dark:border-success-500/30 dark:text-success-500 dark:hover:bg-success-500/10">{{ __('Foydalanuvchini tiklash') }}</button>
            @endif

            {{-- Block modal --}}
            <template x-teleport="body">
                <div x-show="blockOpen" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-gray-900/50" @click="blockOpen = false"></div>
                    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900" @keydown.escape.window="blockOpen = false">
                        <h4 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Foydalanuvchini bloklash') }}</h4>

                        @if ($outstandingLoans->isNotEmpty())
                            {{-- Debt guard: cannot block while books are unreturned. --}}
                            <div class="rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-theme-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-500">
                                <p class="font-medium">{{ __('Foydalanuvchida qaytarilmagan kitob(lar) bor.') }}</p>
                                <p class="mt-1">{{ __('Avval quyidagi kitoblarni qaytarib bo‘lgach, bloklash mumkin bo‘ladi.') }}</p>
                                <ul class="mt-2 list-inside list-disc space-y-1">
                                    @foreach ($outstandingLoans as $loan)
                                        <li>{{ $loan->materialTitle() }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="flex justify-end pt-4">
                                <button type="button" @click="blockOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Yopish') }}</button>
                            </div>
                        @else
                            <form action="{{ route('admin.readers.block', $reader) }}" method="POST" class="space-y-4" x-data="{ mode: '{{ old('blocked_until') ? 'until' : 'permanent' }}' }">
                                @csrf
                                @method('PATCH')

                                <div class="space-y-2">
                                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                        <input type="radio" name="block_mode" value="permanent" x-model="mode" class="text-brand-500" />
                                        {{ __('Butunlay bloklash') }}
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                        <input type="radio" name="block_mode" value="until" x-model="mode" class="text-brand-500" />
                                        {{ __('Muddatli (sanagacha)') }}
                                    </label>
                                </div>

                                <div x-show="mode === 'until'" x-cloak>
                                    <x-admin.form.input
                                        type="date"
                                        name="blocked_until"
                                        :label="__('Qaysi sanagacha')"
                                        :value="old('blocked_until')"
                                        x-bind:disabled="mode !== 'until'"
                                    />
                                </div>

                                <x-admin.form.textarea
                                    name="block_reason"
                                    :label="__('Bloklash sababi')"
                                    :value="old('block_reason')"
                                    :required="true"
                                    :rows="3"
                                />

                                <div class="flex justify-end gap-2 pt-2">
                                    <button type="button" @click="blockOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</button>
                                    <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">{{ __('Bloklash') }}</button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </template>

            {{-- Finish-usage modal --}}
            <template x-teleport="body">
                <div x-show="finishOpen" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-gray-900/50" @click="finishOpen = false"></div>
                    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900" @keydown.escape.window="finishOpen = false">
                        <h4 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Foydalanishni tugatish') }}</h4>

                        @if ($outstandingLoans->isNotEmpty())
                            {{-- Debt guard: cannot finish while books are unreturned. --}}
                            <div class="rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-theme-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-500">
                                <p class="font-medium">{{ __('Foydalanuvchida qaytarilmagan kitob(lar) bor.') }}</p>
                                <p class="mt-1">{{ __('Avval quyidagi kitoblarni qaytarib bo‘lgach, foydalanishni tugatish mumkin bo‘ladi.') }}</p>
                                <ul class="mt-2 list-inside list-disc space-y-1">
                                    @foreach ($outstandingLoans as $loan)
                                        <li>{{ $loan->materialTitle() }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="flex justify-end pt-4">
                                <button type="button" @click="finishOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Yopish') }}</button>
                            </div>
                        @else
                            <form action="{{ route('admin.readers.finish', $reader) }}" method="POST" class="space-y-4">
                                @csrf
                                @method('PATCH')

                                <x-admin.form.textarea
                                    name="left_reason"
                                    :label="__('Sababi')"
                                    :value="old('left_reason')"
                                    :required="true"
                                    :rows="3"
                                    :placeholder="__('masalan: dekret, o‘qishni bitirgan, ishdan ketti')"
                                />

                                <div class="flex justify-end gap-2 pt-2">
                                    <button type="button" @click="finishOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</button>
                                    <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">{{ __('Tasdiqlash') }}</button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </template>

            <button type="button" @click="deleteOpen = true"
                    class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>

            {{-- Delete modal --}}
            <template x-teleport="body">
                <div x-show="deleteOpen" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-gray-900/50" @click="deleteOpen = false"></div>
                    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900" @keydown.escape.window="deleteOpen = false">
                        <h4 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Foydalanuvchini o‘chirish') }}</h4>

                        @if ($outstandingLoans->isNotEmpty())
                            {{-- Debt guard: cannot delete while books are unreturned. --}}
                            <div class="rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-theme-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-500">
                                <p class="font-medium">{{ __('Foydalanuvchida qaytarilmagan kitob(lar) bor.') }}</p>
                                <p class="mt-1">{{ __('Avval quyidagi kitoblarni qaytarib bo‘lgach, o‘chirish mumkin bo‘ladi.') }}</p>
                                <ul class="mt-2 list-inside list-disc space-y-1">
                                    @foreach ($outstandingLoans as $loan)
                                        <li>{{ $loan->materialTitle() }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="flex justify-end pt-4">
                                <button type="button" @click="deleteOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Yopish') }}</button>
                            </div>
                        @else
                            <p class="text-theme-sm text-gray-600 dark:text-gray-400">{{ __('Foydalanuvchini o‘chirishni tasdiqlaysizmi? Bu amalni orqaga qaytarib bo‘lmaydi.') }}</p>
                            <form action="{{ route('admin.readers.destroy', $reader) }}" method="POST" class="mt-4">
                                @csrf
                                @method('DELETE')
                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="deleteOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</button>
                                    <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">{{ __('O‘chirish') }}</button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </template>
        </div>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
    @endif

    @if (session('error'))
        <x-alert type="error" class="mb-5">{{ session('error') }}</x-alert>
    @endif

    <div class="grid grid-cols-12 gap-6">
        {{-- Left: photo + main --}}
        <div class="col-span-12 space-y-6 xl:col-span-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 text-center dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="mx-auto flex h-32 w-32 items-center justify-center overflow-hidden rounded-full bg-brand-50 text-4xl font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                    @if ($reader->photo)
                        <img src="{{ asset('storage/' . $reader->photo) }}" alt="" class="h-full w-full object-cover" />
                    @else
                        {{ mb_strtoupper(mb_substr($reader->full_name, 0, 1)) ?: '👤' }}
                    @endif
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-800 dark:text-white/90">{{ $reader->full_name }}</h3>
                <div class="mt-3 flex flex-wrap justify-center gap-2">
                    <span class="text-theme-xs rounded-full bg-gray-100 px-3 py-1 font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">{{ $reader->type->label() }}</span>
                    <span class="text-theme-xs rounded-full px-3 py-1 font-medium {{ $statusColor[$reader->status->value] ?? '' }}">{{ $reader->status->label() }}</span>
                </div>
                @if ($reader->id_number)
                    <p class="text-theme-sm mt-3 text-gray-500 dark:text-gray-400">{{ __('ID raqami') }}: {{ $reader->id_number }}</p>
                @endif

                {{-- Download reader card (ID-card PDF) --}}
                <a href="{{ route('admin.readers.card', $reader) }}" target="_blank"
                   class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                    <span class="text-base leading-none">🪪</span> {{ __('Kitobxon guvohnomasini yuklab olish') }}
                </a>

                {{-- Block indicator --}}
                @if ($reader->status === \App\Enums\ReaderStatus::Blocked)
                    <div class="mt-4 rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-left text-theme-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-500">
                        @if ($reader->blocked_until)
                            <p class="font-medium">{{ __('Cheklangan') }}: {{ $reader->blocked_until->format('d.m.Y') }} {{ __('gacha') }}</p>
                        @else
                            <p class="font-medium">{{ __('Butunlay bloklangan') }}</p>
                        @endif
                        @if ($reader->block_reason)
                            <p class="mt-1">{{ $reader->block_reason }}</p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Affiliation --}}
            @if ($affiliation)
                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                    <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">{{ $isStudent ? __('O‘qish ma’lumotlari') : __('Ish ma’lumotlari') }}</h3>
                    <dl class="space-y-3">
                        @foreach ($affiliation as $label => $value)
                            <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-800/50">
                                <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                                <dd class="text-theme-sm text-right font-medium text-gray-800 dark:text-white/90">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif
        </div>

        {{-- Right: personal + additional + note --}}
        <div class="col-span-12 space-y-6 xl:col-span-8">
            @if ($personal)
                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                    <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Shaxsiy ma’lumotlar') }}</h3>
                    <dl class="grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
                        @foreach ($personal as $label => $value)
                            <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-800/50">
                                <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                                <dd class="text-theme-sm text-right font-medium text-gray-800 dark:text-white/90">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif

            @if ($additional)
                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                    <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Qo‘shimcha ma’lumotlar') }}</h3>
                    <dl class="grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
                        @foreach ($additional as $label => $value)
                            <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-800/50">
                                <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                                <dd class="text-theme-sm text-right font-medium text-gray-800 dark:text-white/90">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif

            @if ($reader->note)
                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                    <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Izoh') }}</h3>
                    <p class="text-theme-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ $reader->note }}</p>
                </div>
            @endif

            {{-- Warnings (red rules) --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6"
                 x-data="{ warnOpen: {{ $errors->has('reason') || $errors->has('note') ? 'true' : 'false' }} }">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Ogohlantirishlar') }}</h3>
                        <p class="text-theme-sm mt-0.5 {{ $warningCount >= 3 ? 'font-medium text-error-600 dark:text-error-500' : 'text-gray-500 dark:text-gray-400' }}">
                            {{ $warningCount }} / 3 {{ __('ogohlantirish') }}
                        </p>
                    </div>
                    <button type="button" @click="warnOpen = true"
                            class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('Ogohlantirish berish') }}</button>
                </div>

                @if ($warningCount >= 3)
                    <div class="mb-4 rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-theme-sm font-medium text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-500">
                        {{ __('3+ ogohlantirish — foydalanuvchini bloklashni ko‘rib chiqing.') }}
                    </div>
                @endif

                @if ($reader->warnings->isEmpty())
                    <p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Ogohlantirishlar yo‘q.') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-theme-sm">
                            <thead>
                                <tr class="border-b border-gray-100 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                    <th class="px-3 py-2 font-medium">{{ __('Sana') }}</th>
                                    <th class="px-3 py-2 font-medium">{{ __('Sababi') }}</th>
                                    <th class="px-3 py-2 font-medium">{{ __('Izoh') }}</th>
                                    <th class="px-3 py-2 font-medium">{{ __('Kim bergan') }}</th>
                                    <th class="px-3 py-2 font-medium"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($reader->warnings as $warning)
                                    <tr class="border-b border-gray-50 dark:border-gray-800/50">
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $warning->warned_at?->format('d.m.Y') }}</td>
                                        <td class="px-3 py-2 font-medium text-gray-800 dark:text-white/90">{{ $warning->reason->label() }}</td>
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $warning->note ?: '—' }}</td>
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $warning->author?->name ?? '—' }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <button type="button"
                                                    @click="$store.confirm.ask('{{ route('admin.readers.warnings.destroy', [$reader, $warning]) }}', '{{ __('Ogohlantirishni o‘chirishni tasdiqlaysizmi?') }}')"
                                                    class="rounded-lg border border-gray-200 px-3 py-1.5 text-theme-xs font-medium text-red-600 hover:bg-red-50 dark:border-gray-800 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- Give warning modal --}}
                <template x-teleport="body">
                    <div x-show="warnOpen" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center p-4">
                        <div class="fixed inset-0 bg-gray-900/50" @click="warnOpen = false"></div>
                        <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900" @keydown.escape.window="warnOpen = false">
                            <h4 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Ogohlantirish berish') }}</h4>

                            <form action="{{ route('admin.readers.warnings.store', $reader) }}" method="POST" class="space-y-4">
                                @csrf

                                @php $baseInput = 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90'; @endphp
                                <div>
                                    <label for="reason" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Sababi') }}<span class="text-error-500">*</span></label>
                                    <select name="reason" id="reason" required class="{{ $baseInput }} {{ $errors->has('reason') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                                        @foreach (\App\Enums\WarningReason::cases() as $reason)
                                            <option value="{{ $reason->value }}" @selected(old('reason') === $reason->value)>{{ $reason->label() }}</option>
                                        @endforeach
                                    </select>
                                    @error('reason')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                                </div>

                                <x-admin.form.textarea
                                    name="note"
                                    :label="__('Izoh')"
                                    :rows="3"
                                />

                                <div class="flex justify-end gap-2 pt-2">
                                    <button type="button" @click="warnOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</button>
                                    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">{{ __('Berish') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Materials read (lending) --}}
    @php
        $defaultDue = now()->addDays(15)->format('Y-m-d');
    @endphp

    <div class="mt-6" x-data="{
        issueOpen: {{ $errors->has('inventory_number') || $errors->has('due_at') ? 'true' : 'false' }},
        lookupState: 'idle', {{-- idle | loading | found | missing --}}
        lookup: {},
        returnOpen: false,
        returnUrl: '',
        returnCondition: '',
        init() {
            @if (old('inventory_number'))
                this.check(@js(old('inventory_number')));
            @endif
        },
        async check(inventory) {
            const value = (inventory || '').trim();
            if (value === '') { this.lookupState = 'idle'; this.lookup = {}; return; }
            this.lookupState = 'loading';
            try {
                const url = '{{ route('admin.copies.lookup') }}?inventory=' + encodeURIComponent(value);
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                const body = await res.json();
                if (body.found) { this.lookup = body; this.lookupState = 'found'; }
                else { this.lookup = {}; this.lookupState = 'missing'; }
            } catch (e) {
                this.lookup = {}; this.lookupState = 'missing';
            }
        },
        openReturn(url, issuedCondition) {
            this.returnUrl = url;
            this.returnCondition = issuedCondition || '';
            this.returnOpen = true;
        },
    }">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('O‘qigan materiallari') }}</h3>
                <button type="button"
                        @click="issueOpen = true"
                        class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">{{ __('Material berish') }}</button>
            </div>

            {{-- Filter: type + search --}}
            <form method="GET" action="{{ route('admin.readers.show', $reader) }}" class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="sm:w-48">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Turi') }}</label>
                    <select name="material_type"
                            class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90">
                        <option value="">{{ __('Barchasi') }}</option>
                        @foreach ($materialTypes as $type)
                            <option value="{{ $type->value }}" @selected(($materialFilters['material_type'] ?? null) === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Qidiruv') }}</label>
                    <input type="text" name="material_search" value="{{ $materialFilters['search'] ?? '' }}"
                           placeholder="{{ __('Sarlavha yoki inventar raqami...') }}"
                           class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90" />
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-brand-500 hover:bg-brand-600 h-11 rounded-lg px-5 text-sm font-medium text-white transition">{{ __('Qidirish') }}</button>
                    @if (array_filter($materialFilters))
                        <a href="{{ route('admin.readers.show', $reader) }}#materials" class="flex h-11 items-center rounded-lg border border-gray-200 px-4 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400">{{ __('Tozalash') }}</a>
                    @endif
                </div>
            </form>

            @if ($loans->isEmpty())
                <p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Hozircha material berilmagan.') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-theme-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                <th class="px-3 py-2 font-medium">{{ __('Turi') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('Nomi') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('Inventar') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('Olgan vaqti') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('Muddat') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('Qaytargan vaqti') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('Nusxa holati') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('Holat') }}</th>
                                <th class="px-3 py-2 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($loans as $loan)
                                @php $overdue = $loan->isOverdue(); @endphp
                                <tr class="border-b border-gray-50 dark:border-gray-800/50 {{ $overdue ? 'bg-error-50 dark:bg-error-500/10' : '' }}">
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                        <span class="text-theme-xs inline-flex rounded-full bg-gray-100 px-2 py-0.5 font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ $loan->materialType()->label() }}</span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="font-medium text-gray-800 dark:text-white/90">{{ $loan->materialTitle() }}</p>
                                        @if ($loan->materialSubtitle())
                                            <p class="text-theme-xs text-gray-500 dark:text-gray-400">{{ $loan->materialSubtitle() }}</p>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $loan->inventoryNumber() }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $loan->issued_at?->format('d.m.Y H:i') }}</td>
                                    <td class="px-3 py-2 {{ $overdue ? 'font-medium text-error-600 dark:text-error-500' : 'text-gray-700 dark:text-gray-300' }}">{{ $loan->due_at?->format('d.m.Y') }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $loan->returned_at?->format('d.m.Y H:i') ?: '—' }}</td>
                                    <td class="px-3 py-2 text-theme-xs text-gray-600 dark:text-gray-400">
                                        <p>{{ __('Berilganda') }}: {{ $loan->issued_condition?->label() ?? '—' }}</p>
                                        @if ($loan->returned_condition)
                                            <p>{{ __('Qaytarilganda') }}: {{ $loan->returned_condition->label() }}</p>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="text-theme-xs rounded-full px-2.5 py-1 font-medium {{ $loan->status === \App\Enums\LoanStatus::Returned ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500' : ($loan->status === \App\Enums\LoanStatus::Lost ? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' : ($overdue ? 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500' : 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500')) }}">{{ $loan->status->label() }}</span>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        @if ($loan->status === \App\Enums\LoanStatus::OnLoan)
                                            <button type="button"
                                                    @click="openReturn('{{ route('admin.loans.return', $loan) }}', @js($loan->issued_condition?->value))"
                                                    class="rounded-lg border border-gray-200 px-3 py-1.5 text-theme-xs font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Qaytardi') }}</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $loans->links() }}
                </div>
            @endif
        </div>

        {{-- Return (with condition) modal --}}
        <template x-teleport="body">
            <div x-show="returnOpen" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-900/50" @click="returnOpen = false"></div>
                <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900" @keydown.escape.window="returnOpen = false">
                    <h4 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Materialni qaytarish') }}</h4>

                    <form method="POST" :action="returnUrl" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Qaytarilgandagi holati') }}</label>
                            <select name="returned_condition" x-model="returnCondition"
                                    class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <option value="">{{ __('Belgilanmagan') }}</option>
                                @foreach ($copyConditions as $condition)
                                    <option value="{{ $condition->value }}">{{ $condition->label() }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-theme-xs text-gray-400">{{ __('Berilgandagi holati taklif sifatida tanlangan — kerak bo‘lsa o‘zgartiring.') }}</p>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="returnOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</button>
                            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">{{ __('Tasdiqlash') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        {{-- Give material modal --}}
        <template x-teleport="body">
            <div x-show="issueOpen" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-900/50" @click="issueOpen = false"></div>
                <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
                    <h4 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Material berish') }}</h4>

                    <form action="{{ route('admin.readers.loans.store', $reader) }}" method="POST" class="space-y-4">
                        @csrf

                        <x-admin.form.input
                            name="inventory_number"
                            :label="__('Inventar raqami')"
                            :required="true"
                            @blur="check($event.target.value)"
                            @keydown.enter.prevent="check($event.target.value)"
                            autocomplete="off"
                        />

                        {{-- Copy/material info (appears automatically) --}}
                        <div x-show="lookupState === 'loading'" class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Qidirilmoqda...') }}</div>

                        <div x-show="lookupState === 'missing'" x-cloak class="rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-theme-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-500">
                            {{ __('Bunday inventar raqamli nusxa topilmadi.') }}
                        </div>

                        <div x-show="lookupState === 'found' && lookup.type === 'book'" x-cloak class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-white/[0.03]">
                            <p class="font-medium text-gray-800 dark:text-white/90" x-text="lookup.book?.title"></p>
                            <p class="text-theme-sm text-gray-500 dark:text-gray-400" x-text="lookup.book?.authors"></p>
                            <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-theme-xs text-gray-500 dark:text-gray-400">
                                <span x-show="lookup.book?.udc">{{ __('UO‘K') }}: <span x-text="lookup.book?.udc"></span></span>
                                <span x-show="lookup.book?.year">{{ __('Yil') }}: <span x-text="lookup.book?.year"></span></span>
                                <span :class="lookup.available ? 'text-success-600 dark:text-success-500' : 'text-error-600 dark:text-error-500'" x-text="lookup.status"></span>
                            </div>
                        </div>

                        <div x-show="lookupState === 'found' && lookup.type === 'journal_copy'" x-cloak class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-white/[0.03]">
                            <p class="font-medium text-gray-800 dark:text-white/90" x-text="lookup.journal?.name"></p>
                            <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-theme-xs text-gray-500 dark:text-gray-400">
                                <span x-show="lookup.journal?.kind" x-text="lookup.journal?.kind"></span>
                                <span x-show="lookup.journal?.year">{{ __('Yil') }}: <span x-text="lookup.journal?.year"></span></span>
                                <span x-show="lookup.journal?.issue_number">{{ __('Son') }}: <span x-text="lookup.journal?.issue_number"></span></span>
                                <span :class="lookup.available ? 'text-success-600 dark:text-success-500' : 'text-error-600 dark:text-error-500'" x-text="lookup.status"></span>
                            </div>
                        </div>

                        <x-admin.form.input
                            type="date"
                            name="due_at"
                            :label="__('Qaytarish muddati')"
                            :value="old('due_at', $defaultDue)"
                            :required="true"
                        />

                        <x-admin.form.textarea
                            name="note"
                            :label="__('Izoh')"
                            :rows="2"
                        />

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="issueOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</button>
                            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">{{ __('Berish') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>

    {{-- Attended events and competitions --}}
    <div class="mt-6" x-data="{ eventOpen: {{ $errors->has('date') || $errors->has('name') || $errors->has('type') || $errors->has('role') ? 'true' : 'false' }} }">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Qatnashgan tadbir va tanlovlar') }}</h3>
                <button type="button" @click="eventOpen = true"
                        class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">+ {{ __('Qo‘shish') }}</button>
            </div>

            @if ($reader->events->isEmpty())
                <p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Hozircha tadbirlar yo‘q.') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-theme-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                <th class="px-3 py-2 font-medium">{{ __('Sanasi') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('Joyi') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('Nomi') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('Turi') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('Maqsadi') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('Havola') }}</th>
                                <th class="px-3 py-2 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reader->events as $event)
                                <tr class="border-b border-gray-50 dark:border-gray-800/50">
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $event->date?->format('d.m.Y') }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $event->place ?: '—' }}</td>
                                    <td class="px-3 py-2 font-medium text-gray-800 dark:text-white/90">{{ $event->name }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $event->type?->label() }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $event->role?->label() }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                        @if ($event->link)
                                            <a href="{{ $event->link }}" target="_blank" rel="noopener noreferrer"
                                               class="font-medium text-brand-500 hover:text-brand-600">{{ __('Havola') }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <button type="button"
                                                @click="$store.confirm.ask('{{ route('admin.readers.events.destroy', [$reader, $event]) }}', '{{ __('Tadbirni o‘chirishni tasdiqlaysizmi?') }}', 'DELETE')"
                                                class="rounded-lg border border-gray-200 px-3 py-1.5 text-theme-xs font-medium text-red-600 hover:bg-red-50 dark:border-gray-800 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Add event modal --}}
        <template x-teleport="body">
            <div x-show="eventOpen" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-900/50" @click="eventOpen = false"></div>
                <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900" @keydown.escape.window="eventOpen = false">
                    <h4 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Tadbir qo‘shish') }}</h4>

                    <form action="{{ route('admin.readers.events.store', $reader) }}" method="POST" class="space-y-4">
                        @csrf

                        @php $eventInput = 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90'; @endphp

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <x-admin.form.input type="date" name="date" :label="__('Sanasi')" :required="true" :value="old('date')" />
                            <x-admin.form.input name="place" :label="__('Joyi')" :value="old('place')" />
                        </div>

                        <x-admin.form.input name="name" :label="__('Nomi')" :required="true" :value="old('name')" />

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="type" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Turi') }}<span class="text-error-500">*</span></label>
                                <select name="type" id="type" required class="{{ $eventInput }} {{ $errors->has('type') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                                    @foreach (\App\Enums\EventType::cases() as $type)
                                        <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                                    @endforeach
                                </select>
                                @error('type')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="role" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Maqsadi') }}<span class="text-error-500">*</span></label>
                                <select name="role" id="role" required class="{{ $eventInput }} {{ $errors->has('role') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                                    @foreach (\App\Enums\EventRole::cases() as $role)
                                        <option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ $role->label() }}</option>
                                    @endforeach
                                </select>
                                @error('role')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <x-admin.form.input name="link" :label="__('Havola')" :value="old('link')" placeholder="https://" />

                        <x-admin.form.textarea name="note" :label="__('Izoh')" :rows="2" />

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="eventOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</button>
                            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">{{ __('Qo‘shish') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>

    {{-- Computer usage --}}
    <div class="mt-6"
         x-data="computerSessionForm({
             hasErrors: {{ $errors->has('computer_id') || $errors->has('duration_minutes') ? 'true' : 'false' }},
             locations: @js($computers->mapWithKeys(fn ($c) => [$c->id => $c->location?->label() ?? __('—')])),
         })">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Kompyuterdan foydalanish') }}</h3>
                <button type="button" @click="computerOpen = true"
                        class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">+ {{ __('Qo‘shish') }}</button>
            </div>

            @if ($reader->computerSessions->isEmpty())
                <p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Hozircha yozuvlar yo‘q.') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-theme-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                <th class="px-3 py-2 font-medium">{{ __('Berilgan vaqti') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('Kompyuter') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('Joylashuv') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('Maqsadi') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('Qolgan vaqt') }}</th>
                                <th class="px-3 py-2 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reader->computerSessions as $session)
                                <tr class="border-b border-gray-50 dark:border-gray-800/50">
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $session->issued_at->format('d.m.Y H:i') }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                        @if ($session->computer)
                                            {{ $session->computer->computer_number ?? $session->computer->inventory_number }} — {{ $session->computer->model }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $session->location?->label() ?? '—' }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $session->purpose ?: '—' }}</td>
                                    <td class="px-3 py-2" data-computer-session-countdown
                                        x-data="computerSessionCountdown({ expiresAt: @js($session->expires_at?->toIso8601String()), returnedAt: @js($session->returned_at?->toIso8601String()) })">
                                        <template x-if="finished">
                                            <span class="text-gray-500 dark:text-gray-400">{{ __('Tugatilgan') }} ({{ $session->returned_at?->format('d.m.Y H:i') }})</span>
                                        </template>
                                        <template x-if="!finished && remainingLabel !== null">
                                            <span :class="isExpired ? 'font-semibold text-error-600 dark:text-error-500' : 'text-gray-700 dark:text-gray-300'" x-text="remainingLabel"></span>
                                        </template>
                                        <template x-if="!finished && remainingLabel === null">
                                            <span class="text-gray-400">—</span>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            @unless ($session->isFinished())
                                                <form method="POST" action="{{ route('admin.computer-sessions.extend', $session) }}" class="flex items-center gap-1">
                                                    @csrf @method('PATCH')
                                                    <input type="number" name="minutes" value="15" min="1" max="1440"
                                                           class="h-8 w-16 rounded-lg border border-gray-200 bg-transparent px-2 text-theme-xs text-gray-800 focus:outline-hidden dark:border-gray-800 dark:text-white/90" />
                                                    <button type="submit" class="rounded-lg border border-gray-200 px-2 py-1.5 text-theme-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">{{ __('Uzaytirish') }}</button>
                                                </form>
                                                <button type="button"
                                                        @click="$store.confirm.ask('{{ route('admin.computer-sessions.finish', $session) }}', '{{ __('Seansni tugatishni tasdiqlaysizmi?') }}', 'PATCH')"
                                                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-theme-xs font-medium text-success-600 hover:bg-success-50 dark:border-gray-800 dark:hover:bg-success-500/10">{{ __('Tugatish') }}</button>
                                            @endunless
                                            <button type="button"
                                                    @click="$store.confirm.ask('{{ route('admin.readers.computer-sessions.destroy', [$reader, $session]) }}', '{{ __('Yozuvni o‘chirishni tasdiqlaysizmi?') }}', 'DELETE')"
                                                    class="rounded-lg border border-gray-200 px-3 py-1.5 text-theme-xs font-medium text-red-600 hover:bg-red-50 dark:border-gray-800 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Add computer usage modal --}}
        <template x-teleport="body">
            <div x-show="computerOpen" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-900/50" @click="computerOpen = false"></div>
                <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900" @keydown.escape.window="computerOpen = false">
                    <h4 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Kompyuterdan foydalanish') }}</h4>

                    <form action="{{ route('admin.readers.computer-sessions.store', $reader) }}" method="POST" class="space-y-4">
                        @csrf

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            {{-- Computer picked from the registry, by its hand-out number (not the inventory tag) --}}
                            @php $computerInput = 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90'; @endphp
                            <div>
                                <label for="computer_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Kompyuter') }}</label>
                                <select name="computer_id" id="computer_id" x-model="computerId" class="{{ $computerInput }} {{ $errors->has('computer_id') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                                    <option value="">{{ __('Tanlang') }}</option>
                                    @foreach ($computers as $computer)
                                        <option value="{{ $computer->id }}" @selected(old('computer_id') == $computer->id)>{{ $computer->computer_number }} — {{ $computer->model }} ({{ $computer->status->label() }})</option>
                                    @endforeach
                                </select>
                                @error('computer_id')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                            </div>

                            {{-- Read-only, auto-filled from the selected computer — never typed. --}}
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Joylashuv') }}</label>
                                <p class="flex h-11 items-center rounded-lg border border-gray-200 bg-gray-50 px-4 text-sm text-gray-600 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400" x-text="locationPreview"></p>
                            </div>

                            <x-admin.form.input type="number" name="duration_minutes" :label="__('Ajratilgan vaqt (daqiqa)')" :required="true" min="1" max="1440" :value="old('duration_minutes', 60)" />
                            <x-admin.form.input name="purpose" :label="__('Maqsadi')" :value="old('purpose')" />
                        </div>

                        <x-admin.form.textarea name="note" :label="__('Izoh')" :rows="2" />

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="computerOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</button>
                            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">{{ __('Qo‘shish') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>
@endsection
