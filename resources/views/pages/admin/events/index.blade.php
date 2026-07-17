@extends('layouts.admin')

@section('title', __('Tadbirlar'))

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ __('Tadbirlar') }}</h2>
            <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">{{ __('Jami') }}: {{ $events->total() }}</p>
        </div>
        <a href="{{ route('admin.events.create') }}"
           class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
            <span class="text-lg leading-none">+</span> {{ __('Yangi tadbir') }}
        </a>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
    @endif

    {{-- Search / filter --}}
    <form method="GET" action="{{ route('admin.events.index') }}"
          class="mb-5 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] sm:flex-row sm:items-end">
        <div class="flex-1">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Qidirish') }}</label>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Tadbir nomi...') }}"
                   class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90" />
        </div>
        <div class="sm:w-44">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Turi') }}</label>
            <select name="type" class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90">
                <option value="">{{ __('Barchasi') }}</option>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(($filters['type'] ?? null) === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 h-11 rounded-lg px-5 text-sm font-medium text-white transition">{{ __('Qidirish') }}</button>
            @if (array_filter($filters))
                <a href="{{ route('admin.events.index') }}" class="flex h-11 items-center rounded-lg border border-gray-200 px-4 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400">{{ __('Tozalash') }}</a>
            @endif
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="max-w-full overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-800">
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Nomi') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Turi') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Sanasi') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Joyi') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Ishtirokchilar') }}</th>
                        <th class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Amallar') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.02]">
                            <td class="px-5 py-4">
                                <p class="text-theme-sm max-w-xs truncate font-medium text-gray-800 dark:text-white/90">{{ $event->name }}</p>
                                @if ($event->link())
                                    <a href="{{ $event->link() }}" target="_blank" rel="noopener noreferrer" class="text-theme-xs font-medium text-brand-500 hover:text-brand-600">{{ __('Yangilik') }} &rarr;</a>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $event->type->label() }}</td>
                            <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $event->date->format('d.m.Y') }}</td>
                            <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $event->locations->pluck('name')->join(', ') ?: '—' }}</td>
                            <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $event->participants->count() }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.events.edit', $event) }}"
                                       class="text-theme-xs rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">{{ __('Tahrirlash') }}</a>
                                    <button type="button"
                                            @click="$store.confirm.ask(@js(route('admin.events.destroy', $event)), @js(__('«:t» tadbirini o‘chirishni tasdiqlaysizmi?', ['t' => $event->name])))"
                                            class="text-theme-xs rounded-lg border border-red-200 px-3 py-1.5 font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Tadbirlar topilmadi.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $events->links() }}</div>
@endsection
