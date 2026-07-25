@extends('layouts.admin')

@section('title', __('Foydalanuvchi turlari'))

@section('content')
    @php
        $rows = $readerTypes->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'is_student' => $t->is_student,
            'certificate_color' => $t->certificate_color,
            'update_url' => route('admin.lookups.reader-types.update', $t),
            'destroy_url' => route('admin.lookups.reader-types.destroy', $t),
        ])->values();
    @endphp

    <div x-data="readerTypeTable({ storeUrl: '{{ route('admin.lookups.reader-types.store') }}' })">
        <x-admin.lookups.header
            :title="__('Foydalanuvchi turlari')"
            :count="$readerTypes->count()"
            :add-label="__('Yangi tur')" />

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="max-w-full overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Nomi') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Toifasi') }}</th>
                            <th class="px-5 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Guvohnoma rangi') }}</th>
                            <th class="px-5 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Amallar') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-4">
                                    <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $row['name'] }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-theme-xs rounded-full bg-gray-100 px-2.5 py-1 font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                        {{ $row['is_student'] ? __('Talaba') : __('Xodim') }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-2">
                                        <span class="h-4 w-4 rounded-full border border-gray-200" style="background: {{ $row['certificate_color'] }};"></span>
                                        <span class="text-theme-xs text-gray-500 dark:text-gray-400">{{ $row['certificate_color'] }}</span>
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" @click='openEdit(@json($row))'
                                                class="text-theme-xs rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">{{ __('Tahrirlash') }}</button>
                                        <button type="button"
                                                @click="$store.confirm.ask('{{ $row['destroy_url'] }}', '{{ __('O‘chirishni tasdiqlaysizmi?') }}')"
                                                class="text-theme-xs rounded-lg border border-red-200 px-3 py-1.5 font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-admin.lookups.empty :colspan="4" :message="__('Foydalanuvchi turlari topilmadi.')" />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Add/edit modal --}}
        <div x-show="open" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-init="@if ($errors->any()) open = true; @endif">
            <div class="fixed inset-0 bg-gray-900/50" @click="close()"></div>

            <div class="relative w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                        <span x-show="mode === 'create'">{{ __('Yangi qo‘shish') }}: {{ __('Foydalanuvchi turi') }}</span>
                        <span x-show="mode === 'edit'" x-cloak>{{ __('Tahrirlash') }}: {{ __('Foydalanuvchi turi') }}</span>
                    </h3>
                    <button type="button" @click="close()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
                </div>

                <form method="POST" :action="action" class="space-y-4">
                    @csrf
                    <template x-if="mode === 'edit'">
                        <input type="hidden" name="_method" value="PUT" />
                    </template>

                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            {{ __('Nomi') }}<span class="text-error-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" x-model="form.name" required
                               placeholder="{{ __('masalan: Bakalavr talabasi (kechki)') }}"
                               class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 @error('name') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror" />
                        @error('name')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Toifasi') }}</span>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="radio" @click="form.is_student = true" :checked="form.is_student === true" />
                                {{ __('Talaba (o‘qish joyi/mutaxassislik/guruh ko‘rsatiladi)') }}
                            </label>
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="radio" @click="form.is_student = false" :checked="form.is_student === false" />
                                {{ __('Xodim (ish joyi/bo‘lim/lavozim ko‘rsatiladi)') }}
                            </label>
                        </div>
                        <input type="hidden" name="is_student" :value="form.is_student ? 1 : 0" />
                    </div>

                    <div>
                        <label for="certificate_color" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            {{ __('Guvohnoma rangi') }}<span class="text-error-500">*</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="color" id="certificate_color" name="certificate_color" x-model="form.certificate_color"
                                   class="h-11 w-16 rounded-lg border border-gray-300 bg-transparent p-1 dark:border-gray-700" />
                            <span class="text-theme-xs text-gray-500 dark:text-gray-400" x-text="form.certificate_color"></span>
                        </div>
                        @error('certificate_color')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="close()"
                                class="h-11 rounded-lg border border-gray-200 px-5 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">{{ __('Bekor qilish') }}</button>
                        <button type="submit"
                                class="bg-brand-500 hover:bg-brand-600 h-11 rounded-lg px-5 text-sm font-medium text-white transition">{{ __('Saqlash') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
