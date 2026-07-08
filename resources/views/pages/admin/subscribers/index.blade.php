@extends('layouts.admin')

@section('title', __('Obunachilar'))

@section('content')
    {{-- Create/edit happens in a modal (small form) — Alpine holds its state.
         On a validation error the page reloads; we re-open the modal from old input. --}}
    <div
        x-data="{
            open: {{ $errors->any() ? 'true' : 'false' }},
            editing: {{ old('subscriber_id') ? 'true' : 'false' }},
            action: '{{ old('subscriber_id') ? route('admin.subscribers.update', old('subscriber_id')) : route('admin.subscribers.store') }}',
            form: {
                id: @js(old('subscriber_id')),
                full_name: @js(old('full_name', '')),
                position: @js(old('position', '')),
                department: @js(old('department', '')),
                phone: @js(old('phone', '')),
            },
            openCreate() {
                this.editing = false;
                this.action = '{{ route('admin.subscribers.store') }}';
                this.form = { id: null, full_name: '', position: '', department: '', phone: '' };
                this.open = true;
            },
            openEdit(url, data) {
                this.editing = true;
                this.action = url;
                this.form = data;
                this.open = true;
            },
        }"
        @keydown.escape.window="open = false"
    >
        {{-- Title + New subscriber --}}
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ __('Obunachilar') }}</h2>
                <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">{{ __('Jami') }}: {{ $subscribers->total() }}</p>
            </div>
            <button type="button" @click="openCreate()"
                    class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                <span class="text-lg leading-none">+</span> {{ __('Yangi obunachi') }}
            </button>
        </div>

        {{-- Success message --}}
        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        {{-- Search --}}
        <form method="GET" action="{{ route('admin.subscribers.index') }}"
              class="mb-5 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] sm:flex-row sm:items-end">
            <div class="flex-1">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Qidirish') }}</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                       placeholder="{{ __('F.I.SH yoki bo‘lim...') }}"
                       class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90" />
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 h-11 rounded-lg px-5 text-sm font-medium text-white transition">{{ __('Qidirish') }}</button>
                @if (array_filter($filters))
                    <a href="{{ route('admin.subscribers.index') }}" class="flex h-11 items-center rounded-lg border border-gray-200 px-4 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400">{{ __('Tozalash') }}</a>
                @endif
            </div>
        </form>

        {{-- Table --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="max-w-full overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('F.I.SH') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Lavozimi') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Bo‘limi') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Telefon') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Obunalar') }}</th>
                            <th class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Amallar') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($subscribers as $subscriber)
                            <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-4 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $subscriber->full_name }}</td>
                                <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $subscriber->position ?: '—' }}</td>
                                <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $subscriber->department ?: '—' }}</td>
                                <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $subscriber->phone ?: '—' }}</td>
                                <td class="px-5 py-4">
                                    <span class="text-theme-xs inline-flex rounded-full bg-brand-50 px-2.5 py-0.5 font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                        {{ $subscriber->subscriptions_count }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button"
                                                @click="openEdit('{{ route('admin.subscribers.update', $subscriber) }}', { id: {{ $subscriber->id }}, full_name: @js($subscriber->full_name), position: @js($subscriber->position ?? ''), department: @js($subscriber->department ?? ''), phone: @js($subscriber->phone ?? '') })"
                                                class="text-theme-xs rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">{{ __('Tahrirlash') }}</button>
                                        <button type="button"
                                                @click="$store.confirm.ask('{{ route('admin.subscribers.destroy', $subscriber) }}', '{{ __('Obunachini o‘chirishni tasdiqlaysizmi?') }}')"
                                                class="text-theme-xs rounded-lg border border-red-200 px-3 py-1.5 font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <x-admin.icon name="academic-cap" class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" />
                                    <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Obunachilar topilmadi.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-5">
            {{ $subscribers->links() }}
        </div>

        {{-- Create / edit modal --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-900/50" @click="open = false"></div>

            <div class="relative w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                        <span x-show="!editing">{{ __('Yangi obunachi') }}</span>
                        <span x-show="editing" x-cloak>{{ __('Obunachini tahrirlash') }}</span>
                    </h3>
                    <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
                </div>

                <form method="POST" :action="action" class="space-y-4">
                    @csrf
                    <template x-if="editing"><input type="hidden" name="_method" value="PUT" /></template>
                    <input type="hidden" name="subscriber_id" :value="form.id" />

                    <div>
                        <label for="full_name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            {{ __('F.I.SH') }}<span class="text-error-500">*</span>
                        </label>
                        <input type="text" name="full_name" id="full_name" x-model="form.full_name" required
                               placeholder="{{ __('To‘liq ism sharif') }}"
                               class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 @error('full_name') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror" />
                        @error('full_name')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="position" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Lavozimi') }}</label>
                            <input type="text" name="position" id="position" x-model="form.position"
                                   placeholder="{{ __('masalan: dotsent') }}"
                                   class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        </div>
                        <div>
                            <label for="department" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Bo‘limi') }}</label>
                            <input type="text" name="department" id="department" x-model="form.department"
                                   placeholder="{{ __('bo‘lim / kafedra') }}"
                                   class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        </div>
                    </div>

                    <div>
                        <label for="phone" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Telefon') }}</label>
                        <input type="text" name="phone" id="phone" x-model="form.phone"
                               placeholder="+998 __ ___ __ __"
                               class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="open = false"
                                class="h-11 rounded-lg border border-gray-200 px-5 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">{{ __('Bekor qilish') }}</button>
                        <button type="submit"
                                class="bg-brand-500 hover:bg-brand-600 h-11 rounded-lg px-5 text-sm font-medium text-white transition">{{ __('Saqlash') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
