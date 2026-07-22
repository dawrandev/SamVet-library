@extends('layouts.admin')

@section('title', __('Kompyuterlar'))

@section('content')
    @php
        // Status badge colors (keyed by ComputerStatus::color())
        $statusBadge = [
            'success' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
            'error' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
            'warning' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500',
        ];
    @endphp

    {{-- Create/edit happens in a modal (compact form). Alpine holds the state;
         on a validation error the page reloads and the modal re-opens from old input. --}}
    <div
        x-data="{
            open: {{ $errors->any() ? 'true' : 'false' }},
            editing: {{ old('computer_id') ? 'true' : 'false' }},
            action: '{{ old('computer_id') ? route('admin.computers.update', old('computer_id')) : route('admin.computers.store') }}',
            form: {
                id: @js(old('computer_id')),
                model: @js(old('model', '')),
                type: @js(old('type', '')),
                inventory_number: @js(old('inventory_number', '')),
                computer_number: @js(old('computer_number', '')),
                status: @js(old('status', 'working')),
                location: @js(old('location', '')),
                note: @js(old('note', '')),
            },
            openCreate() {
                this.editing = false;
                this.action = '{{ route('admin.computers.store') }}';
                this.form = { id: null, model: '', type: '', inventory_number: '', computer_number: '', status: 'working', location: '', note: '' };
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
        {{-- Title + New computer --}}
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ __('Kompyuterlar') }}</h2>
                <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">{{ __('Jami') }}: {{ $computers->total() }}</p>
            </div>
            <button type="button" @click="openCreate()"
                    class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                <span class="text-lg leading-none">+</span> {{ __('Yangi kompyuter') }}
            </button>
        </div>

        {{-- Success message --}}
        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        {{-- Search / filter --}}
        <form method="GET" action="{{ route('admin.computers.index') }}"
              class="mb-5 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] sm:flex-row sm:items-end">
            <div class="flex-1">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Qidirish') }}</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                       placeholder="{{ __('Modeli yoki inventar raqami...') }}"
                       class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90" />
            </div>
            <div class="sm:w-40">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Turi') }}</label>
                <select name="type"
                        class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90">
                    <option value="">{{ __('Barchasi') }}</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(($filters['type'] ?? null) === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:w-40">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Holati') }}</label>
                <select name="status"
                        class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90">
                    <option value="">{{ __('Barchasi') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:w-44">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Joylashuv') }}</label>
                <select name="location"
                        class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-800 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-white/90">
                    <option value="">{{ __('Barchasi') }}</option>
                    @foreach (\App\Enums\ComputerLocation::cases() as $opt)
                        <option value="{{ $opt->value }}" @selected(($filters['location'] ?? null) === $opt->value)>{{ $opt->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 h-11 rounded-lg px-5 text-sm font-medium text-white transition">{{ __('Qidirish') }}</button>
                @if (array_filter($filters))
                    <a href="{{ route('admin.computers.index') }}" class="flex h-11 items-center rounded-lg border border-gray-200 px-4 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400">{{ __('Tozalash') }}</a>
                @endif
            </div>
        </form>

        {{-- Table --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="max-w-full overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Modeli') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Turi') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Inventar raqami') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Kompyuter raqami') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Holati') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Joylashuv') }}</th>
                            <th class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Amallar') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($computers as $computer)
                            <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center overflow-hidden rounded-md bg-gray-100 dark:bg-gray-800"><x-admin.icon name="computer-desktop" class="h-5 w-5 text-gray-400" /></div>
                                        <p class="text-theme-sm truncate font-medium text-gray-800 dark:text-white/90">{{ $computer->model }}</p>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-theme-xs inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">{{ $computer->type?->label() ?? '—' }}</span>
                                </td>
                                <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $computer->inventory_number }}</td>
                                <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $computer->computer_number ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    <span class="text-theme-xs inline-flex rounded-full px-2.5 py-0.5 font-medium {{ $statusBadge[$computer->status?->color()] ?? '' }}">{{ $computer->status?->label() ?? '—' }}</span>
                                </td>
                                <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $computer->location?->label() ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.computers.show', $computer) }}"
                                           class="text-theme-xs rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">{{ __('Ko‘rish') }}</a>
                                        <button type="button"
                                                @click="openEdit('{{ route('admin.computers.update', $computer) }}', { id: {{ $computer->id }}, model: @js($computer->model), type: @js($computer->type?->value), inventory_number: @js($computer->inventory_number), computer_number: @js($computer->computer_number ?? ''), status: @js($computer->status?->value), location: @js($computer->location?->value ?? ''), note: @js($computer->note ?? '') })"
                                                class="text-theme-xs rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">{{ __('Tahrirlash') }}</button>
                                        <button type="button"
                                                @click="$store.confirm.ask('{{ route('admin.computers.destroy', $computer) }}', '{{ __('Kompyuterni o‘chirishni tasdiqlaysizmi?') }}')"
                                                class="text-theme-xs rounded-lg border border-red-200 px-3 py-1.5 font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <x-admin.icon name="computer-desktop" class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" />
                                    <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Kompyuterlar topilmadi.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-5">
            {{ $computers->links() }}
        </div>

        {{-- Create / edit modal --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-gray-900/50" @click="open = false"></div>

            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="relative max-h-[85vh] w-full max-w-xl overflow-y-auto rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-5 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                            <span x-show="!editing">{{ __('Yangi kompyuter') }}</span>
                            <span x-show="editing" x-cloak>{{ __('Kompyuterni tahrirlash') }}</span>
                        </h3>
                        <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
                    </div>

                    <form method="POST" :action="action" class="space-y-4">
                    @csrf
                    <template x-if="editing"><input type="hidden" name="_method" value="PUT" /></template>
                    <input type="hidden" name="computer_id" :value="form.id" />

                    <div>
                        <label for="model" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Modeli') }}<span class="text-error-500">*</span></label>
                        <input type="text" name="model" id="model" x-model="form.model" required
                               placeholder="{{ __('masalan: HP ProDesk 400 G7') }}"
                               class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 @error('model') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror" />
                        @error('model')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="type" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Turi') }}<span class="text-error-500">*</span></label>
                            <select name="type" id="type" x-model="form.type" required
                                    class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 @error('type') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror">
                                <option value="">{{ __('Tanlang') }}</option>
                                @foreach (\App\Enums\ComputerType::cases() as $opt)
                                    <option value="{{ $opt->value }}">{{ $opt->label() }}</option>
                                @endforeach
                            </select>
                            @error('type')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Holati') }}<span class="text-error-500">*</span></label>
                            <select name="status" id="status" x-model="form.status" required
                                    class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 @error('status') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror">
                                @foreach (\App\Enums\ComputerStatus::cases() as $opt)
                                    <option value="{{ $opt->value }}">{{ $opt->label() }}</option>
                                @endforeach
                            </select>
                            @error('status')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="inventory_number" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Inventar raqami') }}<span class="text-error-500">*</span></label>
                            <input type="text" name="inventory_number" id="inventory_number" x-model="form.inventory_number" required
                                   placeholder="{{ __('masalan: KMP-001') }}"
                                   class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 @error('inventory_number') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror" />
                            @error('inventory_number')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                            <p class="mt-1 text-theme-xs text-gray-400">{{ __('Ochiq saytda ko‘rinmaydi.') }}</p>
                        </div>
                        <div>
                            <label for="computer_number" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Kompyuter raqami') }}</label>
                            <input type="text" name="computer_number" id="computer_number" x-model="form.computer_number"
                                   placeholder="{{ __('masalan: 1') }}"
                                   class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 @error('computer_number') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror" />
                            @error('computer_number')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                            <p class="mt-1 text-theme-xs text-gray-400">{{ __('Kompyuterga kutubxonachi tomonidan berilgan raqam.') }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="location" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Joylashuv') }}</label>
                            <select name="location" id="location" x-model="form.location"
                                    class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <option value="">{{ __('Tanlang') }}</option>
                                @foreach (\App\Enums\ComputerLocation::cases() as $opt)
                                    <option value="{{ $opt->value }}">{{ $opt->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="note" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Eslatma') }}</label>
                        <textarea name="note" id="note" x-model="form.note" rows="3"
                                  class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
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
    </div>
@endsection
