@extends('layouts.admin')

@section('title', $computer->model)

@section('content')
    @php
        // Status badge colors (keyed by ComputerStatus::color())
        $statusBadge = [
            'success' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
            'error' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
            'warning' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500',
        ];
    @endphp

    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.computers.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ $computer->model }}</h2>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.computers.edit', $computer) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Tahrirlash') }}</a>
            <button type="button"
                    @click="$store.confirm.ask('{{ route('admin.computers.destroy', $computer) }}', '{{ __('Kompyuterni o‘chirishni tasdiqlaysizmi?') }}')"
                    class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
        </div>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
    @endif

    <div class="grid grid-cols-12 gap-6">
        {{-- Left: computer details --}}
        <div class="col-span-12 space-y-6 xl:col-span-5">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <div class="mb-5 flex items-center gap-3">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                        <x-admin.icon name="computer-desktop" class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ $computer->model }}</h3>
                        <p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $computer->type?->label() }}</p>
                    </div>
                </div>

                <dl class="space-y-3">
                    <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-800/50">
                        <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Turi') }}</dt>
                        <dd class="text-theme-sm text-right font-medium text-gray-800 dark:text-white/90">{{ $computer->type?->label() ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-800/50">
                        <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Inventar raqami') }}</dt>
                        <dd class="flex items-center gap-2">
                            <span class="text-theme-xs rounded-full bg-gray-100 px-2 py-0.5 font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ __('INVENTAR') }}</span>
                            <span class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $computer->inventory_number }}</span>
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-800/50">
                        <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Kompyuter raqami') }}</dt>
                        <dd class="text-theme-sm text-right font-medium text-gray-800 dark:text-white/90">{{ $computer->computer_number ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-800/50">
                        <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Holati') }}</dt>
                        <dd>
                            <span class="text-theme-xs inline-flex rounded-full px-2.5 py-0.5 font-medium {{ $statusBadge[$computer->status?->color()] ?? '' }}">{{ $computer->status?->label() ?? '—' }}</span>
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4 pb-1">
                        <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Joylashuv') }}</dt>
                        <dd class="text-theme-sm text-right font-medium text-gray-800 dark:text-white/90">{{ $computer->location?->label() ?? '—' }}</dd>
                    </div>
                </dl>

                <p class="text-theme-sm mt-5 rounded-lg bg-gray-50 px-4 py-3 leading-relaxed text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
                    {{ __('Axborot resurs markazi Elektron o‘qish zalida joylashgan.') }}
                </p>
            </div>
        </div>

        {{-- Right: note --}}
        @if ($computer->note)
            <div class="col-span-12 space-y-6 xl:col-span-7">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                    <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Eslatma') }}</h3>
                    <p class="text-theme-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ $computer->note }}</p>
                </div>
            </div>
        @endif
    </div>
@endsection
