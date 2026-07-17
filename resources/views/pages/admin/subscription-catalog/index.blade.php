@extends('layouts.admin')

@section('title', __('Obuna katalogi'))

@section('content')
    <div
        x-data="{
            open: {{ $errors->any() && ! old('_catalog_id') ? 'true' : 'false' }},
            editing: {{ old('_catalog_id') ? 'true' : 'false' }},
            action: '{{ old('_catalog_id') ? route('admin.subscription-catalog.update', old('_catalog_id')) : route('admin.subscription-catalog.store') }}',
            form: {
                id: @js(old('_catalog_id')),
                journal_id: @js(old('journal_id', '')),
                annual_price: @js(old('annual_price', '')),
                is_selected: @js((bool) old('is_selected', true)),
            },
            openCreate() {
                this.editing = false;
                this.action = '{{ route('admin.subscription-catalog.store') }}';
                this.form = { id: null, journal_id: '', annual_price: '', is_selected: true };
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
        {{-- Title --}}
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ __('Obuna katalogi') }}</h2>
                <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">{{ __('Rasmiy yillik katalog — belgilanganlar obuna formasida ko‘rinadi') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.subscriptions.index') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">
                    {{ __('Obunalarga qaytish') }}
                </a>
                <button type="button" @click="openCreate()"
                        class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                    <span class="text-lg leading-none">+</span> {{ __('Katalogga qo‘shish') }}
                </button>
            </div>
        </div>

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        {{-- Year picker --}}
        <form method="GET" action="{{ route('admin.subscription-catalog.index') }}"
              class="mb-5 flex items-end gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="sm:w-32">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Yil') }}</label>
                <input type="number" name="year" value="{{ $year }}"
                       class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90" />
            </div>
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 h-11 rounded-lg px-5 text-sm font-medium text-white transition">{{ __('Ko‘rsatish') }}</button>
            @if (count($years))
                <div class="flex items-center gap-1.5 pb-0.5">
                    @foreach ($years as $y)
                        <a href="{{ route('admin.subscription-catalog.index', ['year' => $y]) }}"
                           class="text-theme-xs rounded-lg px-2.5 py-1.5 font-medium {{ $y === $year ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'text-gray-500 hover:bg-gray-100 dark:text-gray-400' }}">{{ $y }}</a>
                    @endforeach
                </div>
            @endif
        </form>

        {{-- Table --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="max-w-full overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Nashr') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Indeks') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Yillik summa') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('1 oylik summa') }}</th>
                            <th class="px-5 py-3 text-center text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Bizga kerak') }}</th>
                            <th class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Amallar') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-4 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $entry->journal?->name ?? '—' }}</td>
                                <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $entry->journal?->index ?? '—' }}</td>
                                <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ number_format($entry->annual_price, 0, '.', ' ') }} {{ __('so‘m') }}</td>
                                <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ number_format($entry->annual_price / 12, 0, '.', ' ') }} {{ __('so‘m') }}</td>
                                <td class="px-5 py-4 text-center">
                                    <form method="POST" action="{{ route('admin.subscription-catalog.update', $entry) }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="year" value="{{ $entry->year }}" />
                                        <input type="hidden" name="journal_id" value="{{ $entry->journal_id }}" />
                                        <input type="hidden" name="annual_price" value="{{ $entry->annual_price }}" />
                                        <input type="hidden" name="is_selected" value="{{ $entry->is_selected ? 0 : 1 }}" />
                                        <button type="submit"
                                                class="text-lg leading-none {{ $entry->is_selected ? 'text-success-600' : 'text-gray-300 dark:text-gray-700' }}"
                                                title="{{ __('Ichki katalogga kiritish/chiqarish') }}">
                                            {{ $entry->is_selected ? '✅' : '⬜' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button"
                                                @click="openEdit('{{ route('admin.subscription-catalog.update', $entry) }}', { id: {{ $entry->id }}, journal_id: @js((string) $entry->journal_id), annual_price: @js((string) $entry->annual_price), is_selected: @js((bool) $entry->is_selected) })"
                                                class="text-theme-xs rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">{{ __('Tahrirlash') }}</button>
                                        <button type="button"
                                                @click="$store.confirm.ask('{{ route('admin.subscription-catalog.destroy', $entry) }}', '{{ __('Katalogdan o‘chirishni tasdiqlaysizmi?') }}')"
                                                class="text-theme-xs rounded-lg border border-red-200 px-3 py-1.5 font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-theme-sm text-gray-500 dark:text-gray-400">{{ __(':year yil uchun katalog bo‘sh.', ['year' => $year]) }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Add / edit modal --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-900/50" @click="open = false"></div>

            <div class="relative w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                        <span x-show="!editing">{{ __('Katalogga qo‘shish') }}</span>
                        <span x-show="editing" x-cloak>{{ __('Katalog yozuvini tahrirlash') }}</span>
                    </h3>
                    <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
                </div>

                <form method="POST" :action="action" class="space-y-4">
                    @csrf
                    <template x-if="editing"><input type="hidden" name="_method" value="PUT" /></template>
                    <input type="hidden" name="_catalog_id" :value="form.id" />
                    <input type="hidden" name="year" value="{{ $year }}" />

                    <div>
                        <label for="c_journal" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Nashr') }}<span class="text-error-500">*</span></label>
                        <select name="journal_id" id="c_journal" x-model="form.journal_id" required
                                class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 @error('journal_id') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror">
                            <option value="">{{ __('Tanlang') }}</option>
                            @foreach ($journals as $j)
                                <option value="{{ $j->id }}">{{ $j->name }}{{ $j->index ? " ({$j->index})" : '' }}</option>
                            @endforeach
                        </select>
                        @error('journal_id')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="c_price" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Yillik summa (so‘m)') }}<span class="text-error-500">*</span></label>
                        <input type="number" name="annual_price" id="c_price" x-model="form.annual_price" required min="0" step="1"
                               placeholder="{{ __('masalan: 1800000') }}"
                               class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 @error('annual_price') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror" />
                        @error('annual_price')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                        <p class="mt-1.5 text-theme-xs text-gray-400">{{ __('Rasmiy katalogdagi yillik summa — 1 oylik narx shundan avtomat hisoblanadi.') }}</p>
                    </div>

                    <label class="flex items-center gap-2.5">
                        <input type="hidden" name="is_selected" value="0" />
                        <input type="checkbox" name="is_selected" value="1" x-model="form.is_selected"
                               class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/20 dark:border-gray-700" />
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Bizga kerak (ichki katalogga kiritilsin)') }}</span>
                    </label>

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
