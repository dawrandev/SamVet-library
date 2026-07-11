@props([
    'name',
    'label' => null,
    'accept' => null,
    'help' => null,
    'image' => false,       // rasm bo'lsa preview ko'rsatiladi
    'currentUrl' => null,    // tahrirda mavjud fayl (rasm URL yoki nom)
    'currentName' => null,   // mavjud fayl nomi (rasm bo'lmasa)
    'withProgress' => false, // show an upload progress bar (form must use x-data="uploadForm")
])

<div>
    @if ($label)
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ $label }}</label>
    @endif

    <div x-data="{
            fileName: @js($currentName),
            preview: @js($image ? $currentUrl : null),
            handle(e) {
                const f = e.target.files[0];
                if (!f) return;
                this.fileName = f.name;
                if ({{ $image ? 'true' : 'false' }}) this.preview = URL.createObjectURL(f);
            }
         }">
        <label class="flex cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-6 text-center transition hover:border-brand-400 hover:bg-gray-100 dark:border-gray-700 dark:bg-white/[0.02] dark:hover:bg-white/[0.05]">
            <input type="file" name="{{ $name }}" class="hidden" @if ($accept) accept="{{ $accept }}" @endif @change="handle($event)" {{ $attributes }} />

            {{-- Rasm preview --}}
            @if ($image)
                <template x-if="preview">
                    <img :src="preview" alt="" class="mb-3 h-28 w-20 rounded-lg object-cover shadow-sm" />
                </template>
            @endif

            <span class="text-gray-400" @if ($image) x-show="!preview" @endif><x-admin.icon name="upload" class="h-6 w-6" /></span>

            <p class="mt-1 text-theme-sm font-medium text-gray-700 dark:text-gray-300">
                <span x-show="!fileName">{{ __('Faylni tanlang') }}</span>
                <span x-show="fileName" x-text="fileName" class="text-brand-500"></span>
            </p>
            @if ($help)
                <p class="mt-1 text-theme-xs text-gray-400">{{ $help }}</p>
            @endif
        </label>

        @if ($withProgress)
            {{-- Upload progress (reads state from the parent x-data="uploadForm"). --}}
            <div x-show="uploading" x-cloak class="mt-3">
                <div class="mb-1 flex items-center justify-between text-theme-xs">
                    <span class="font-medium text-gray-600 dark:text-gray-300" x-text="processing ? '{{ __('Serverda saqlanmoqda...') }}' : '{{ __('Yuklanmoqda...') }}'"></span>
                    <span class="text-gray-500 dark:text-gray-400" x-text="progress + '%' + (progressText ? ' · ' + progressText : '')"></span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                    <div class="h-full rounded-full bg-brand-500 transition-all duration-150"
                         :class="processing && 'animate-pulse'"
                         :style="`width: ${progress}%`"></div>
                </div>
                <p x-show="processing" x-cloak class="mt-1.5 text-theme-xs text-gray-400">{{ __('Fayl yuklandi. Katta fayl saqlanishi biroz vaqt olishi mumkin — sahifani yopmang.') }}</p>
            </div>
        @endif
    </div>

    @error($name)
        <p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
