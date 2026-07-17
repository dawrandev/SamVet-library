@extends('layouts.admin')

@section('title', __('Berilgan kitoblar'))

@php
    $scope = $filters['scope'] ?? 'overdue';
    $today = now()->startOfDay();

    $tabs = [
        'overdue' => __('Muddati o‘tgan'),
        'due_soon' => __('Yaqin (3 kun)'),
        'active' => __('Barcha faol'),
    ];
@endphp

@section('content')
    {{-- Title --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ __('Berilgan kitoblar') }}</h2>
            <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">
                {{ __('Muddati o‘tgan kitoblar') }}:
                <span class="font-semibold text-error-600 dark:text-error-500">{{ $overdueLoansCount ?? 0 }}</span>
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
                <a href="{{ route('admin.loans.index', ['scope' => $key, 'search' => $filters['search'] ?? null]) }}"
                   @class([
                       'text-theme-sm rounded-md px-3 py-2 font-medium transition',
                       'shadow-theme-xs bg-white text-gray-900 dark:bg-gray-800 dark:text-white' => $scope === $key,
                       'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' => $scope !== $key,
                   ])>
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Type + search --}}
        <form method="GET" action="{{ route('admin.loans.index') }}" class="flex gap-2">
            <input type="hidden" name="scope" value="{{ $scope }}" />
            <select name="material_type"
                    class="shadow-theme-xs h-11 rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90">
                <option value="">{{ __('Barchasi') }}</option>
                @foreach ($materialTypes as $type)
                    <option value="{{ $type->value }}" @selected(($filters['material_type'] ?? null) === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                   placeholder="{{ __('Foydalanuvchi yoki inventar raqami...') }}"
                   class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90 lg:w-72" />
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 h-11 rounded-lg px-5 text-sm font-medium text-white transition">{{ __('Qidirish') }}</button>
            @if (! empty($filters['search']) || ! empty($filters['material_type']))
                <a href="{{ route('admin.loans.index', ['scope' => $scope]) }}" class="flex h-11 items-center rounded-lg border border-gray-200 px-4 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400">{{ __('Tozalash') }}</a>
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
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Turi') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Material') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Berilgan sana') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Muddat') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Kechikkan') }}</th>
                        <th class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Amallar') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($loans as $loan)
                        @php
                            $isOverdue = $loan->due_at !== null && $loan->due_at->lt($today);
                            $daysLate = $isOverdue ? $loan->due_at->diffInDays($today) : null;
                            $daysLeft = (! $isOverdue && $loan->due_at !== null) ? $today->diffInDays($loan->due_at) : null;
                        @endphp
                        <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.02]">
                            {{-- Reader --}}
                            <td class="px-5 py-4">
                                @if ($loan->reader)
                                    <a href="{{ route('admin.readers.show', $loan->reader) }}"
                                       class="text-theme-sm font-medium text-brand-600 hover:underline dark:text-brand-400">
                                        {{ $loan->reader->full_name }}
                                    </a>
                                @else
                                    <span class="text-theme-sm text-gray-400">—</span>
                                @endif
                            </td>
                            {{-- Type --}}
                            <td class="px-5 py-4">
                                <span class="text-theme-xs inline-flex rounded-full bg-gray-100 px-2 py-0.5 font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ $loan->materialType()->label() }}</span>
                            </td>
                            {{-- Material --}}
                            <td class="px-5 py-4">
                                <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $loan->materialTitle() }}</p>
                                <p class="text-theme-xs text-gray-500 dark:text-gray-400">{{ __('Inventar') }}: {{ $loan->inventoryNumber() ?? '—' }}</p>
                            </td>
                            {{-- Issued date --}}
                            <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $loan->issued_at?->format('d.m.Y') ?? '—' }}</td>
                            {{-- Due date --}}
                            <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $loan->due_at?->format('d.m.Y') ?? '—' }}</td>
                            {{-- Overdue --}}
                            <td class="px-5 py-4">
                                @if ($isOverdue)
                                    <span class="text-theme-xs inline-flex rounded-full bg-error-50 px-2.5 py-0.5 font-medium text-error-600 dark:bg-error-500/15 dark:text-error-500">
                                        {{ $daysLate }} {{ __('kun') }}
                                    </span>
                                @elseif ($daysLeft !== null)
                                    <span class="text-theme-xs text-gray-500 dark:text-gray-400">{{ __(':n kun qoldi', ['n' => $daysLeft]) }}</span>
                                @else
                                    <span class="text-theme-xs text-gray-400">—</span>
                                @endif
                            </td>
                            {{-- Actions --}}
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button"
                                            @click="$store.confirm.ask('{{ route('admin.loans.return', $loan) }}', '{{ __('Kitob qaytarilganini tasdiqlaysizmi?') }}', 'PATCH')"
                                            class="text-theme-xs rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">
                                        {{ __('Qaytardi') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center">
                                <x-admin.icon name="book" class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" />
                                <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Kitoblar topilmadi.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-5">
        {{ $loans->links() }}
    </div>
@endsection
