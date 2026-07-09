<footer class="mt-16 bg-blue-900 text-white">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-4">
            {{-- Brand --}}
            <div>
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-blue-700 text-white">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" /></svg>
                    </span>
                    <span class="text-base font-bold">SamVMChBTU · {{ __('Nukus filiali') }}</span>
                </div>
                <p class="mt-4 text-sm leading-relaxed text-white/60">
                    {{ __('Axborot resurs markazi (ARM). Nukus shahri, universitet filiali hududi. Ta‘lim va ilm-fan uchun ochiq raqamli fond.') }}
                </p>
            </div>

            {{-- Quick links --}}
            <div>
                <h3 class="text-sm font-semibold text-white">{{ __('Tezkor havolalar') }}</h3>
                <ul class="mt-4 space-y-2.5 text-sm text-white/70">
                    <li><a href="#" class="transition hover:text-white">{{ __('Elektron katalog') }}</a></li>
                    <li><a href="#" class="transition hover:text-white">{{ __('Bo‘limlar') }}</a></li>
                    <li><a href="#" class="transition hover:text-white">{{ __('Yangiliklar') }}</a></li>
                    <li><a href="#" class="transition hover:text-white">{{ __('ARM haqida') }}</a></li>
                </ul>
            </div>

            {{-- Sections --}}
            <div>
                <h3 class="text-sm font-semibold text-white">{{ __('Bo‘limlar') }}</h3>
                <ul class="mt-4 space-y-2.5 text-sm text-white/70">
                    <li><a href="#" class="transition hover:text-white">{{ __('Darsliklar') }}</a></li>
                    <li><a href="#" class="transition hover:text-white">{{ __('Dissertatsiyalar') }}</a></li>
                    <li><a href="#" class="transition hover:text-white">{{ __('Jurnallar') }}</a></li>
                    <li><a href="#" class="transition hover:text-white">{{ __('Gazetalar') }}</a></li>
                </ul>
            </div>

            {{-- Contacts --}}
            <div>
                <h3 class="text-sm font-semibold text-white">{{ __('Aloqa') }}</h3>
                <ul class="mt-4 space-y-2.5 text-sm text-white/70">
                    <li>{{ __('Tel') }}: +998 (61) 000-00-00</li>
                    <li>Email: arm@samvmcbtu-nukus.uz</li>
                    <li>{{ __('Dush–Shan') }}: 09:00 – 18:00</li>
                </ul>
            </div>
        </div>

        <div class="mt-10 flex flex-col gap-2 border-t border-white/10 pt-6 text-xs text-white/50 sm:flex-row sm:items-center sm:justify-between">
            <span>© {{ date('Y') }} {{ __('Axborot resurs markazi. Barcha huquqlar himoyalangan.') }}</span>
            <span>{{ __('Materiallar faqat onlayn o‘qish uchun · Saytdan foydalanish qoidalari') }}</span>
        </div>
    </div>
</footer>
