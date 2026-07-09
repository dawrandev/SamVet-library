{{-- Accessibility bar + language switcher (dark navy strip above the header). --}}
<div class="bg-blue-900 text-white">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 px-4 py-2 text-xs sm:px-6 lg:px-8">
        <span class="flex items-center gap-2 text-white/70">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
            {{ __('Ko‘zi ojizlar uchun qulay ko‘rinish') }}
        </span>

        <div class="flex items-center gap-3">
            {{-- Font size --}}
            <div class="flex items-center gap-1">
                <span class="text-white/60">{{ __('Shrift') }}</span>
                <button type="button" @click="zoom(-10)" aria-label="{{ __('Shriftni kichraytirish') }}"
                        class="flex h-6 w-6 items-center justify-center rounded border border-white/25 text-white/90 transition hover:bg-white/10">A−</button>
                <button type="button" @click="zoom(10)" aria-label="{{ __('Shriftni kattalashtirish') }}"
                        class="flex h-6 w-6 items-center justify-center rounded border border-white/25 text-white/90 transition hover:bg-white/10">A+</button>
            </div>

            {{-- Contrast --}}
            <button type="button" @click="contrast = !contrast"
                    :class="contrast ? 'bg-white text-blue-900' : 'border border-white/25 text-white/90 hover:bg-white/10'"
                    class="flex items-center gap-1.5 rounded px-2 py-1 transition">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 0 20V2Z" /><path fill="none" stroke="currentColor" stroke-width="1.5" d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Z" /></svg>
                {{ __('Kontrast') }}
            </button>

            {{-- Language switcher --}}
            <div class="flex items-center gap-0.5">
                @foreach (config('locale.supported') as $code => $label)
                    <a href="{{ route('locale.switch', $code) }}"
                       @class([
                           'flex h-6 items-center rounded px-2 font-medium uppercase transition',
                           'bg-white text-blue-900' => app()->getLocale() === $code,
                           'text-white/80 hover:bg-white/10' => app()->getLocale() !== $code,
                       ])>{{ $code }}</a>
                @endforeach
            </div>
        </div>
    </div>
</div>
