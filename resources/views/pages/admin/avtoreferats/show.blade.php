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
            __('Holati') => $avtoreferat->condition?->label(),
            __('Nashr joyi') => $avtoreferat->publicationPlace?->name,
            __('Himoya yili') => $avtoreferat->defense_year,
            __('Inventari') => $avtoreferat->inventory_number,
            __('Tillari') => $avtoreferat->languages->pluck('name')->implode(', ') ?: null,
        ], fn ($v) => filled($v));
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
