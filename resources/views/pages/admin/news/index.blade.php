@extends('layouts.admin')

@section('title', __('Yangiliklar'))

@section('content')
    @php
        $statusColor = [
            'published' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
            'draft' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
        ];
    @endphp

    {{-- Title --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ __('Yangiliklar') }}</h2>
            <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">{{ __('Jami') }}: {{ $news->total() }}</p>
        </div>
        <a href="{{ route('admin.news.create') }}"
           class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
            <span class="text-lg leading-none">+</span> {{ __('Yangi yangilik') }}
        </a>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
    @endif

    {{-- Search / filter --}}
    <form method="GET" action="{{ route('admin.news.index') }}"
          class="mb-5 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] sm:flex-row sm:items-end">
        <div class="flex-1">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Qidirish') }}</label>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Sarlavha...') }}"
                   class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90" />
        </div>
        <div class="sm:w-44">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Kategoriya') }}</label>
            <select name="news_category_id" class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90">
                <option value="">{{ __('Barchasi') }}</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(($filters['news_category_id'] ?? null) == $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:w-40">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Holat') }}</label>
            <select name="status" class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90">
                <option value="">{{ __('Barchasi') }}</option>
                @foreach (\App\Enums\NewsStatus::cases() as $st)
                    <option value="{{ $st->value }}" @selected(($filters['status'] ?? null) === $st->value)>{{ $st->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 h-11 rounded-lg px-5 text-sm font-medium text-white transition">{{ __('Qidirish') }}</button>
            @if (array_filter($filters))
                <a href="{{ route('admin.news.index') }}" class="flex h-11 items-center rounded-lg border border-gray-200 px-4 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400">{{ __('Tozalash') }}</a>
            @endif
        </div>
    </form>

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="max-w-full overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-800">
                        <th class="w-12 px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('№') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Yangilik') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Kategoriya') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Holat') }}</th>
                        <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Nashr sanasi') }}</th>
                        <th class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Amallar') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($news as $item)
                        <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.02]">
                            <td class="px-5 py-4 text-theme-sm font-medium text-gray-500 dark:text-gray-400">{{ $news->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-16 flex-none items-center justify-center overflow-hidden rounded-lg bg-gray-100 text-lg dark:bg-gray-800">
                                        @if ($item->cover_image)
                                            <img src="{{ asset('storage/' . $item->cover_image) }}" alt="" class="h-full w-full object-cover" />
                                        @else
                                            <x-admin.icon name="newspaper" class="h-6 w-6 text-gray-400" />
                                        @endif
                                    </div>
                                    <p class="text-theme-sm max-w-xs truncate font-medium text-gray-800 dark:text-white/90">{{ $item->getTranslation('title', 'uz') ?: '—' }}</p>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $item->category?->name ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <span class="text-theme-xs inline-flex rounded-full px-2.5 py-0.5 font-medium {{ $statusColor[$item->status->value] ?? '' }}">{{ $item->status->label() }}</span>
                            </td>
                            <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $item->published_at?->format('d.m.Y H:i') ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.news.edit', $item) }}"
                                       class="text-theme-xs rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">{{ __('Tahrirlash') }}</a>
                                    <button type="button"
                                            @click="$store.confirm.ask(@js(route('admin.news.destroy', $item)), @js(__('«:t» yangiligini o‘chirishni tasdiqlaysizmi?', ['t' => $item->getTranslation('title', 'uz')])))"
                                            class="text-theme-xs rounded-lg border border-red-200 px-3 py-1.5 font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <x-admin.icon name="newspaper" class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" />
                                <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Yangiliklar topilmadi.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $news->links() }}</div>
@endsection
