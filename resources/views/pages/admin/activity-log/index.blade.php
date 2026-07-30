@extends('layouts.admin')

@section('title', __('Faoliyat jurnali'))

@php
    use App\Enums\AdminActivityAction;

    $subjectTypes = ['Reader' => __('O‘quvchi'), 'Book' => __('Kitob'), 'BookCopy' => __('Kitob nusxasi'), 'Subscription' => __('Obuna'), 'Loan' => __('Ijara')];
@endphp

@section('content')
    {{-- Title --}}
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ __('Faoliyat jurnali') }}</h2>
        <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">{{ __('Adminlar tomonidan bajarilgan amallar — kim, nimani, qachon o‘zgartirdi.') }}</p>
    </div>

    {{-- Filters --}}
    <div class="mb-5 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] lg:flex-row lg:items-center">
        <form method="GET" action="{{ route('admin.activity-log.index') }}" class="flex flex-wrap items-center gap-2">
            <select name="subject_type" onchange="this.form.submit()"
                    class="shadow-theme-xs h-11 rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90">
                <option value="">{{ __('Barcha turlar') }}</option>
                @foreach ($subjectTypes as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['subject_type'] ?? null) === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="action" onchange="this.form.submit()"
                    class="shadow-theme-xs h-11 rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90">
                <option value="">{{ __('Barcha amallar') }}</option>
                @foreach (AdminActivityAction::cases() as $case)
                    <option value="{{ $case->value }}" @selected(($filters['action'] ?? null) === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </select>

            @if (! empty($filters['subject_type']) || ! empty($filters['action']))
                <a href="{{ route('admin.activity-log.index') }}" class="flex h-11 items-center rounded-lg border border-gray-200 px-4 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400">{{ __('Tozalash') }}</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="max-w-full overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-800">
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Admin') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Amal') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Obyekt') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('O‘zgarishlar') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Vaqti') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.02]">
                            <td class="px-5 py-4 text-theme-sm font-medium text-gray-700 dark:text-gray-300">{{ $log->admin?->name ?? __('Noma’lum') }}</td>
                            <td class="px-5 py-4">
                                <span @class([
                                    'text-theme-xs rounded-full px-2.5 py-1 font-medium',
                                    'bg-success-50 text-success-600 dark:bg-success-500/10' => $log->action === AdminActivityAction::Created,
                                    'bg-warning-50 text-warning-600 dark:bg-warning-500/10' => $log->action === AdminActivityAction::Updated,
                                    'bg-error-50 text-error-600 dark:bg-error-500/10' => $log->action === AdminActivityAction::Deleted,
                                ])>{{ $log->action->label() }}</span>
                            </td>
                            <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $subjectTypes[$log->subject_type] ?? $log->subject_type }} #{{ $log->subject_id }}</td>
                            <td class="px-5 py-4 text-theme-xs text-gray-500 dark:text-gray-400">
                                @if ($log->changes)
                                    <ul class="space-y-0.5">
                                        @foreach ($log->changes as $field => [$old, $new])
                                            <li><span class="font-medium text-gray-600 dark:text-gray-300">{{ $field }}</span>: {{ $old ?? '—' }} → {{ $new ?? '—' }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-5 py-4 text-theme-sm whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $log->created_at->format('d.m.Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center">
                                <x-admin.icon name="document-text" class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" />
                                <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Yozuvlar topilmadi.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-5">
        {{ $logs->links() }}
    </div>
@endsection
