@extends('layouts.admin')

@section('title', __('Kompyuter seanslari'))

@php
    $scope = $filters['scope'] ?? 'active';

    $tabs = [
        'active' => __('Faol'),
        'expired' => __('Muddati tugagan'),
        'finished' => __('Tugatilgan'),
    ];
@endphp

@section('content')
    {{-- Title --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ __('Kompyuter seanslari') }}</h2>
            <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">
                {{ __('Muddati tugagan seanslar') }}:
                <span class="font-semibold text-error-600 dark:text-error-500">{{ $expiredComputerSessionsCount ?? 0 }}</span>
            </p>
        </div>
    </div>

    {{-- Success message --}}
    @if (session('success'))
        <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
    @endif

    {{-- Filter tab buttons + search --}}
    <div class="mb-5 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] lg:flex-row lg:items-center lg:justify-between">
        {{-- Scope tabs --}}
        <div class="inline-flex w-fit items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-gray-900">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('admin.computer-sessions.index', ['scope' => $key, 'search' => $filters['search'] ?? null]) }}"
                   @class([
                       'text-theme-sm rounded-md px-3 py-2 font-medium transition',
                       'shadow-theme-xs bg-white text-gray-900 dark:bg-gray-800 dark:text-white' => $scope === $key,
                       'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' => $scope !== $key,
                   ])>
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Search --}}
        <form method="GET" action="{{ route('admin.computer-sessions.index') }}" class="flex gap-2">
            <input type="hidden" name="scope" value="{{ $scope }}" />
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                   placeholder="{{ __('Foydalanuvchi yoki kompyuter raqami...') }}"
                   class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90 lg:w-72" />
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 h-11 rounded-lg px-5 text-sm font-medium text-white transition">{{ __('Qidirish') }}</button>
            @if (! empty($filters['search']))
                <a href="{{ route('admin.computer-sessions.index', ['scope' => $scope]) }}" class="flex h-11 items-center rounded-lg border border-gray-200 px-4 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400">{{ __('Tozalash') }}</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="max-w-full overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-800">
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Foydalanuvchi') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Kompyuter') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Joylashuv') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Berilgan vaqti') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Maqsadi') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Qolgan vaqt') }}</th>
                        <th class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Amallar') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sessions as $session)
                        <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.02]">
                            <td class="px-5 py-4">
                                @if ($session->reader)
                                    <a href="{{ route('admin.readers.show', $session->reader) }}"
                                       class="text-theme-sm font-medium text-brand-600 hover:underline dark:text-brand-400">
                                        {{ $session->reader->full_name }}
                                    </a>
                                @else
                                    <span class="text-theme-sm text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">
                                @if ($session->computer)
                                    {{ $session->computer->computer_number ?? $session->computer->inventory_number }} — {{ $session->computer->model }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $session->location?->label() ?? '—' }}</td>
                            <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $session->issued_at->format('d.m.Y H:i') }}</td>
                            <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $session->purpose ?: '—' }}</td>
                            <td class="px-5 py-4" data-computer-session-countdown
                                x-data="computerSessionCountdown({ expiresAt: @js($session->expires_at?->toIso8601String()), returnedAt: @js($session->returned_at?->toIso8601String()) })">
                                <template x-if="finished">
                                    <span class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Tugatilgan') }} ({{ $session->returned_at?->format('d.m.Y H:i') }})</span>
                                </template>
                                <template x-if="!finished && remainingLabel !== null">
                                    <span class="text-theme-sm" :class="isExpired ? 'font-semibold text-error-600 dark:text-error-500' : 'text-gray-700 dark:text-gray-300'" x-text="remainingLabel"></span>
                                </template>
                                <template x-if="!finished && remainingLabel === null">
                                    <span class="text-theme-sm text-gray-400">—</span>
                                </template>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1.5">
                                    @unless ($session->isFinished())
                                        <form method="POST" action="{{ route('admin.computer-sessions.extend', $session) }}" class="flex items-center gap-1">
                                            @csrf @method('PATCH')
                                            <input type="number" name="minutes" value="15" min="1" max="1440"
                                                   class="h-8 w-16 rounded-lg border border-gray-200 bg-transparent px-2 text-theme-xs text-gray-800 focus:outline-hidden dark:border-gray-800 dark:text-white/90" />
                                            <button type="submit" class="text-theme-xs rounded-lg border border-gray-200 px-2 py-1.5 font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">{{ __('Uzaytirish') }}</button>
                                        </form>
                                        <button type="button"
                                                @click="$store.confirm.ask('{{ route('admin.computer-sessions.finish', $session) }}', '{{ __('Seansni tugatishni tasdiqlaysizmi?') }}', 'PATCH')"
                                                class="text-theme-xs rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-success-600 hover:bg-success-50 dark:border-gray-800 dark:hover:bg-success-500/10">{{ __('Tugatish') }}</button>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center">
                                <x-admin.icon name="computer-desktop" class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" />
                                <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Seanslar topilmadi.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-5">
        {{ $sessions->links() }}
    </div>
@endsection
