<footer class="mt-16 bg-blue-900 text-white">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-4">
            {{-- Brand --}}
            <div>
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 flex-none items-center justify-center rounded-xl bg-white/95 p-1">
                        <img src="{{ asset('images/samvet/logo.png') }}" alt="{{ __('SDVUNF Nukus filiali logotipi') }}"
                             class="h-full w-full object-contain" width="44" height="44" />
                    </span>
                    <span class="text-base font-bold">{{ __('SamDVMChBU Nukus filiali') }}</span>
                </div>
                <p class="mt-4 text-sm leading-relaxed text-white/60">
                    {{ __('Samarqand davlat veterinariya meditsinasi, chorvachilik va biotexnologiyalar universiteti Nukus filiali Axborot resurs markazi.') }}
                </p>
            </div>

            {{-- Quick links --}}
            <div>
                <h3 class="text-sm font-semibold text-white">{{ __('Tezkor havolalar') }}</h3>
                <ul class="mt-4 space-y-2.5 text-sm text-white/70">
                    <li><a href="{{ route('catalog') }}" class="transition hover:text-white">{{ __('Elektron katalog') }}</a></li>
                    <li><a href="{{ route('sections') }}" class="transition hover:text-white">{{ __('Bo‘limlar') }}</a></li>
                    <li><a href="{{ route('news.index') }}" class="transition hover:text-white">{{ __('Yangiliklar') }}</a></li>
                    <li><a href="{{ route('statistics') }}" class="transition hover:text-white">{{ __('Statistika') }}</a></li>
                    @if ($armUrl)
                        <li><a href="{{ $armUrl }}" class="transition hover:text-white">{{ __('ARM haqida') }}</a></li>
                    @endif
                </ul>
            </div>

            {{-- Sections (built from the fund, so the links always resolve) --}}
            <div>
                <h3 class="text-sm font-semibold text-white">{{ __('Bo‘limlar') }}</h3>
                <ul class="mt-4 space-y-2.5 text-sm text-white/70">
                    @foreach ($footerSections as $section)
                        <li><a href="{{ $section['url'] }}" class="transition hover:text-white">{{ $section['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            {{-- Contacts --}}
            <div>
                <h3 class="text-sm font-semibold text-white">{{ __('Murojaat uchun') }}</h3>
                <ul class="mt-4 space-y-2.5 text-sm text-white/70">
                    <li>{{ __('Manzil') }}: {{ __('230102, Nukus shahar, A. Utepov ko‘chasi 31 uy') }}</li>
                    <li>{{ __('Email') }}: samvminf@edu.uz</li>
                    <li>{{ __('Telefon') }}: +998 61 224 71 09</li>
                    <li>{{ __('Ish vaqti') }}: {{ __('Dush–Juma') }} 09:00 – 17:00, {{ __('Shanba') }} 09:00 – 15:00</li>
                </ul>
            </div>
        </div>

        <div class="mt-10 flex flex-col gap-2 border-t border-white/10 pt-6 text-xs text-white/50 sm:flex-row sm:items-center sm:justify-between">
            <span>© {{ date('Y') }} {{ __('Axborot resurs markazi. Barcha huquqlar himoyalangan.') }}</span>
            <span>{{ __('Materiallar faqat onlayn o‘qish uchun · Saytdan foydalanish qoidalari') }}</span>
        </div>
    </div>
</footer>
