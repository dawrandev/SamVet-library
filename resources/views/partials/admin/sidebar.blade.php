{{-- Sidebar: mobil'da chap tomondan suriladi, lg'da doim ko'rinadi --}}
<aside
    x-cloak
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-30 flex w-64 flex-col border-r border-gray-200 bg-white transition-transform duration-200 lg:translate-x-0"
>
    {{-- Logo --}}
    <div class="flex h-16 items-center gap-3 border-b border-gray-200 px-6">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white">📚</div>
        <span class="truncate font-semibold text-gray-900">{{ config('app.name') }}</span>
    </div>

    {{-- Navigatsiya --}}
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        <x-admin.nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" icon="🏠">
            {{ __('Bosh sahifa') }}
        </x-admin.nav-link>

        {{-- Kelajakda qo'shiladigan bo'limlar --}}
        <x-admin.nav-link href="#" icon="📕">{{ __('Kitoblar') }}</x-admin.nav-link>
        <x-admin.nav-link href="#" icon="🗂️">{{ __('Kategoriyalar') }}</x-admin.nav-link>
        <x-admin.nav-link href="#" icon="👥">{{ __('Foydalanuvchilar') }}</x-admin.nav-link>
    </nav>

    {{-- Pastki qism: chiqish --}}
    <div class="border-t border-gray-200 p-3">
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-red-600 transition hover:bg-red-50">
                <span class="text-lg">🚪</span> {{ __('Chiqish') }}
            </button>
        </form>
    </div>
</aside>
