@extends('layouts.admin')

@section('title', __('Kitoblarni import qilish'))

@section('content')
    @php($stats = session('import_stats'))

    {{-- Sarlavha --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ __('Kitoblarni Exceldan import qilish') }}</h2>
            <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">
                {{ __('Kitoblar ro‘yxati (.xlsx) faylini yuklang — har qator bitta nusxa, bir xil kitoblar guruhlanadi.') }}
            </p>
        </div>
        <a href="{{ route('admin.books.index') }}"
           class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.06]">
            ← {{ __('Ro‘yxatga qaytish') }}
        </a>
    </div>

    {{-- Import xatosi --}}
    @if (session('import_error'))
        <x-alert type="error" class="mb-5">{{ session('import_error') }}</x-alert>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Yuklash formasi --}}
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('admin.books.import.store') }}" enctype="multipart/form-data"
                  x-data="{
                      fileName: '',
                      dragging: false,
                      loading: false,
                      hint: '',
                      hints: [
                          '{{ __('Fayl serverga yuklanmoqda...') }}',
                          '{{ __('Kitoblar o‘qilmoqda...') }}',
                          '{{ __('Nusxalar bazaga saqlanmoqda...') }}',
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

                {{-- Drag & drop maydon --}}
                <label
                    @dragover.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="dragging = false; if ($event.dataTransfer.files.length) { $refs.file.files = $event.dataTransfer.files; fileName = $refs.file.files[0].name }"
                    :class="dragging ? 'border-brand-500 bg-brand-50 dark:bg-brand-500/10' : 'border-gray-300 dark:border-gray-700'"
                    class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-6 py-10 text-center transition">
                    <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-3xl dark:bg-brand-500/15">📚</div>
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

                {{-- Import indikatori (to'liq ekran overlay) --}}
                <template x-teleport="body">
                    <div x-show="loading" x-cloak
                         class="fixed inset-0 z-999999 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm">
                        <div class="w-full max-w-sm rounded-2xl bg-white p-8 text-center shadow-2xl dark:bg-gray-900">
                            <div class="border-t-brand-500 mx-auto mb-5 h-12 w-12 animate-spin rounded-full border-4 border-gray-200 dark:border-gray-700"></div>
                            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                                {{ __('Kitoblar import qilinmoqda') }}
                            </h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-text="hint"></p>
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

        {{-- Yo'riqnoma --}}
        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-6 dark:border-gray-800 dark:bg-white/[0.02]">
            <h3 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">{{ __('Qanday ishlaydi?') }}</h3>
            <ul class="text-theme-sm space-y-2 text-gray-600 dark:text-gray-400">
                <li>• {{ __('Har qator — bitta nusxa. Bir xil kitobning nusxalari bitta kitobga guruhlanadi.') }}</li>
                <li>• {{ __('Turi, til, nashriyot, joylashuv, muallif yo‘q bo‘lsa avtomatik yaratiladi.') }}</li>
                <li>• {{ __('Matn qanday bo‘lsa shundayligicha saqlanadi (kirill/lotin) — keyin admin tozalaydi.') }}</li>
                <li>• {{ __('Bir xil inventar raqami takroran qo‘shilmaydi.') }}</li>
                <li>• {{ __('Kategoriya, narx va elektron fayl bu faylda yo‘q — keyin qo‘lda beriladi.') }}</li>
            </ul>
        </div>
    </div>

    {{-- Import natijasi --}}
    @if ($stats)
        <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="mb-5 flex items-center gap-2">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500">✓</span>
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Import yakunlandi') }}</h3>
            </div>

            {{-- Asosiy natijalar --}}
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
                    <p class="text-theme-xs text-gray-500 dark:text-gray-400">{{ __('Yangi kitoblar') }}</p>
                    <p class="mt-1 text-title-sm font-bold text-success-600 dark:text-success-500">{{ $stats['books'] }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
                    <p class="text-theme-xs text-gray-500 dark:text-gray-400">{{ __('Qo‘shilgan nusxalar') }}</p>
                    <p class="mt-1 text-title-sm font-bold text-brand-600 dark:text-brand-400">{{ $stats['copies'] }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
                    <p class="text-theme-xs text-gray-500 dark:text-gray-400">{{ __('O‘tkazib yuborilgan') }}</p>
                    <p class="mt-1 text-title-sm font-bold text-gray-500 dark:text-gray-400">{{ $stats['skipped'] }}</p>
                </div>
            </div>

            {{-- Avtomatik yaratilgan ma'lumotnomalar --}}
            <h4 class="mt-6 mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Avtomatik yaratilgan ma’lumotnomalar') }}</h4>
            <div class="flex flex-wrap gap-2 text-theme-sm">
                @foreach ([
                    __('Muallif') => $stats['authors'],
                    __('Kitob turi') => $stats['book_types'],
                    __('Til') => $stats['languages'],
                    __('Nashriyot') => $stats['publishers'],
                    __('Joylashuv') => $stats['locations'],
                ] as $label => $count)
                    <span class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-gray-600 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
                        {{ $label }}: <span class="font-semibold text-gray-800 dark:text-white/90">{{ $count }}</span>
                    </span>
                @endforeach
            </div>

            <a href="{{ route('admin.books.index') }}"
               class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 mt-6 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                {{ __('Kitoblar ro‘yxatini ko‘rish') }} →
            </a>
        </div>
    @endif
@endsection
