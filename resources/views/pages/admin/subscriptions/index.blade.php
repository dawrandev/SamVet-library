@extends('layouts.admin')

@section('title', __('Obunalar'))

@section('content')
    {{-- Title + New subscription --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ __('Obunalar') }}</h2>
            <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">{{ __('Jami') }}: {{ $subscriptions->total() }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.subscriptions.create') }}"
               class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                <span class="text-lg leading-none">+</span> {{ __('Yangi obuna') }}
            </a>
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
                                <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $subscription->reader?->affiliationGroup?->name ?? '—' }}</td>
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
                                        <a href="{{ route('admin.subscriptions.edit', $subscription) }}"
                                           class="text-theme-xs rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">{{ __('Tahrirlash') }}</a>
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
@endsection
