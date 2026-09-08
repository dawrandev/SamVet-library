@extends('layouts.admin')

@section('title', __('Exceldan import'))

@section('content')
    @php($stats = session('import_stats'))

    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ __('Foydalanuvchilarni Exceldan import qilish') }}</h2>
            <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">
                {{ __('Kutubxona guvohnomasi (.xlsx) faylini yuklang — barcha varaqlar avtomatik o‘qiladi.') }}
            </p>
        </div>
        <a href="{{ route('admin.readers.index') }}"
           class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.06]">
            ← {{ __('Ro‘yxatga qaytish') }}
        </a>
    </div>

    {{-- Import error --}}
    @if (session('import_error'))
        <x-alert type="error" class="mb-5">{{ session('import_error') }}</x-alert>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Upload form --}}
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('admin.readers.import.store') }}" enctype="multipart/form-data"
                  x-data="{
                      fileName: '',
                      dragging: false,
                      loading: false,
                      hint: '',
                      hints: [
                          '{{ __('Fayl serverga yuklanmoqda...') }}',
                          '{{ __('Excel varaqlari o‘qilmoqda...') }}',
                          '{{ __('Foydalanuvchilar bazaga saqlanmoqda...') }}',
                          '{{ __('Deyarli tayyor, biroz kuting...') }}',
                      ],
                      start() {
                          if (! this.$refs.file.files.length) return false;
                          this.loading = true;
                          let i = 0;
                          this.hint = this.hints[0];
                          setInterval(() => { i = (i + 1) % this.hints.length; this.hint = this.hints[i]; }, 2500);
                          return true;
                      }
                  }"
                  @submit="if (! start()) $event.preventDefault()"
                  class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                @csrf

                {{-- Drag & drop area --}}
                <label
                    @dragover.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="dragging = false; if ($event.dataTransfer.files.length) { $refs.file.files = $event.dataTransfer.files; fileName = $refs.file.files[0].name }"
                    :class="dragging ? 'border-brand-500 bg-brand-50 dark:bg-brand-500/10' : 'border-gray-300 dark:border-gray-700'"
                    class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-6 py-10 text-center transition">
                    <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400"><x-admin.icon name="document-text" class="h-6 w-6" /></div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        <span x-show="!fileName">{{ __('Faylni bu yerga tashlang yoki tanlash uchun bosing') }}</span>
                        <span x-show="fileName" x-text="fileName" class="text-brand-600 dark:text-brand-400"></span>
                    </p>
                    <p class="text-theme-xs mt-1 text-gray-500 dark:text-gray-400">{{ __('.xlsx yoki .xls — maksimum 100 MB') }}</p>
                    <input type="file" name="file" x-ref="file" accept=".xlsx,.xls" class="hidden"
                           @change="fileName = $refs.file.files.length ? $refs.file.files[0].name : ''" />
                </label>

                @error('file')
                    <p class="mt-2 text-sm text-error-600 dark:text-error-500">{{ $message }}</p>
                @enderror

                <button type="submit" x-ref="submitBtn" :disabled="loading"
                        class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 mt-5 inline-flex w-full items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white transition disabled:opacity-60 sm:w-auto">
                    <span>{{ __('Import qilish') }}</span>
                </button>

                {{-- Import indicator (full-screen overlay) --}}
                <template x-teleport="body">
                    <div x-show="loading" x-cloak
                         class="fixed inset-0 z-999999 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm">
                        <div class="w-full max-w-sm rounded-2xl bg-white p-8 text-center shadow-2xl dark:bg-gray-900">
                            {{-- Spinner --}}
                            <div class="border-t-brand-500 mx-auto mb-5 h-12 w-12 animate-spin rounded-full border-4 border-gray-200 dark:border-gray-700"></div>

                            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                                {{ __('Foydalanuvchilar import qilinmoqda') }}
                            </h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-text="hint"></p>

                            {{-- Moving (indeterminate) progress bar --}}
                            <div class="mt-5 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                <div class="bg-brand-500 animate-indeterminate h-full w-1/4 rounded-full"></div>
                            </div>

                            <p class="mt-4 text-xs text-gray-400">
                                {{ __('Iltimos, sahifani yopmang. Bu bir necha daqiqa vaqt olishi mumkin.') }}
                            </p>
                        </div>
                    </div>
                </template>
            </form>
        </div>

        {{-- Instructions --}}
        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-6 dark:border-gray-800 dark:bg-white/[0.02]">
            <h3 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">{{ __('Qanday ishlaydi?') }}</h3>
            <ul class="text-theme-sm space-y-2 text-gray-600 dark:text-gray-400">
                <li>• {{ __('Har bir varaq (BT, PO, ST, TT ...) mos foydalanuvchi turiga bog‘lanadi.') }}</li>
                <li>• {{ __('«Ketkenler» varag‘i — foydalanish tugatilgan (left) sifatida saqlanadi.') }}</li>
                <li>• {{ __('ID raqami yoki JSHSHIR bo‘yicha takror yozuvlar yangilanadi (dublikat yaratilmaydi).') }}</li>
                <li>• {{ __('Bo‘sh yoki xizmatchi qatorlar (Pechat, ID nomer) o‘tkazib yuboriladi.') }}</li>
            </ul>
        </div>
    </div>

    {{-- Import result --}}
    @if ($stats)
        @php($problems = $stats['problems'] ?? [])
        @php($needsAttention = (bool) ($stats['needs_attention'] ?? false))

        {{--
            The modal opens by itself only when something is actually wrong with
            the file. Trailing blank rows are normal and must not nag; a name
            column the importer could not find is what silently dropped 563 rows
            once, and that has to be impossible to miss.
        --}}
        <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]"
             x-data="{ showProblems: @js($needsAttention) }">
            <div class="mb-4 flex flex-wrap items-center gap-2">
                @if ($needsAttention)
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500">!</span>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Import yakunlandi — diqqat talab qiladi') }}</h3>
                @else
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500">✓</span>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Import yakunlandi') }}</h3>
                @endif

                @if ($problems)
                    <button type="button" @click="showProblems = true"
                            class="text-theme-sm text-brand-600 dark:text-brand-400 ml-auto font-medium underline-offset-2 hover:underline">
                        {{ __('Nega o‘tkazib yuborildi?') }}
                    </button>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-gray-500 dark:border-gray-800 dark:text-gray-400">
                            <th class="py-2.5 pr-4 font-medium">{{ __('Varaq') }}</th>
                            <th class="py-2.5 pr-4 font-medium">{{ __('Turi') }}</th>
                            <th class="py-2.5 pr-4 font-medium text-right">{{ __('Yangi') }}</th>
                            <th class="py-2.5 pr-4 font-medium text-right">{{ __('Yangilandi') }}</th>
                            <th class="py-2.5 pr-4 font-medium text-right">{{ __('O‘tkazildi') }}</th>
                            <th class="py-2.5 font-medium text-right">{{ __('Rasm') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($stats['sheets'] as $row)
                            <tr class="text-gray-700 dark:text-gray-300">
                                <td class="py-2.5 pr-4 font-medium">{{ $row['sheet'] }}</td>
                                <td class="py-2.5 pr-4">{{ $row['type'] }}</td>
                                <td class="py-2.5 pr-4 text-right text-success-600 dark:text-success-500">{{ $row['imported'] }}</td>
                                <td class="py-2.5 pr-4 text-right text-brand-600 dark:text-brand-400">{{ $row['updated'] }}</td>
                                <td class="py-2.5 pr-4 text-right text-gray-400">{{ $row['skipped'] }}</td>
                                <td class="py-2.5 text-right text-gray-600 dark:text-gray-300">{{ $row['photos'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-200 font-semibold text-gray-800 dark:border-gray-700 dark:text-white/90">
                            <td class="py-3 pr-4" colspan="2">{{ __('JAMI') }}</td>
                            <td class="py-3 pr-4 text-right text-success-600 dark:text-success-500">{{ $stats['total']['imported'] }}</td>
                            <td class="py-3 pr-4 text-right text-brand-600 dark:text-brand-400">{{ $stats['total']['updated'] }}</td>
                            <td class="py-3 pr-4 text-right text-gray-400">{{ $stats['total']['skipped'] }}</td>
                            <td class="py-3 text-right text-gray-600 dark:text-gray-300">{{ $stats['total']['photos'] }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <a href="{{ route('admin.readers.index') }}"
               class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 mt-5 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                {{ __('Foydalanuvchilar ro‘yxatini ko‘rish') }} →
            </a>

            {{-- Why rows were skipped --}}
            @if ($problems)
                <template x-teleport="body">
                    <div x-show="showProblems" x-cloak
                         @keydown.escape.window="showProblems = false"
                         class="z-999999 fixed inset-0 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm">
                        <div @click.outside="showProblems = false"
                             class="max-h-[85vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900">
                            <div class="mb-4 flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                                        {{ __('Qatorlar nega o‘tkazib yuborildi?') }}
                                    </h3>
                                    <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">
                                        {{ __('Har bir varaq bo‘yicha sabablar. Bo‘sh qatorlar odatiy holat.') }}
                                    </p>
                                </div>
                                <button type="button" @click="showProblems = false"
                                        class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/[0.06]">
                                    ✕
                                </button>
                            </div>

                            <div class="space-y-5">
                                @foreach ($problems as $problem)
                                    <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                                        <h4 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">
                                            {{ __('Varaq') }}: {{ $problem['sheet'] }}
                                        </h4>

                                        @if ($problem['error'])
                                            <x-alert type="error" class="mb-3">
                                                {{ __('Varaqni o‘qib bo‘lmadi') }}: {{ $problem['error'] }}
                                            </x-alert>
                                        @endif

                                        {{-- The one failure a librarian can fix in Excel without help. --}}
                                        @if ($problem['missing_columns'])
                                            <x-alert type="error" class="mb-3">
                                                <p class="font-medium">{{ __('Faylda quyidagi ustun topilmadi:') }}</p>
                                                <ul class="mt-1 list-inside list-disc">
                                                    @foreach ($problem['missing_columns'] as $column)
                                                        <li>{{ $column }}</li>
                                                    @endforeach
                                                </ul>
                                                <p class="mt-2">
                                                    {{ __('Excel’da ustun sarlavhasini to‘g‘rilab, faylni qayta yuklang.') }}
                                                </p>
                                            </x-alert>

                                            @if ($problem['headers'])
                                                <div class="mb-3">
                                                    <p class="text-theme-xs mb-1 font-medium text-gray-500 dark:text-gray-400">
                                                        {{ __('Fayldagi mavjud ustunlar') }}:
                                                    </p>
                                                    <div class="flex flex-wrap gap-1.5">
                                                        @foreach ($problem['headers'] as $header)
                                                            <span class="text-theme-xs rounded-md bg-gray-100 px-2 py-1 text-gray-600 dark:bg-white/[0.06] dark:text-gray-300">{{ $header }}</span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        @endif

                                        @if ($problem['issues'])
                                            <table class="min-w-full text-sm">
                                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                                    @foreach ($problem['issues'] as $issue)
                                                        <tr>
                                                            <td class="py-2 pr-4 {{ $issue['attention'] ? 'text-warning-600 dark:text-warning-500 font-medium' : 'text-gray-600 dark:text-gray-300' }}">
                                                                {{ $issue['label'] }}
                                                            </td>
                                                            <td class="py-2 text-right tabular-nums text-gray-700 dark:text-gray-300">
                                                                {{ $issue['count'] }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <button type="button" @click="showProblems = false"
                                    class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 mt-5 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                                {{ __('Yopish') }}
                            </button>
                        </div>
                    </div>
                </template>
            @endif
        </div>
    @endif
@endsection
