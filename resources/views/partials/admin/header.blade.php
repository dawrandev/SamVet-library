<header class="sticky top-0 z-10 flex h-16 items-center gap-4 border-b border-gray-200 bg-white px-4 sm:px-6">
    {{-- Mobil uchun menyu tugmasi --}}
    <button @click="sidebarOpen = true" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 lg:hidden">
        <span class="text-xl">☰</span>
    </button>

    <h1 class="text-lg font-semibold text-gray-900">@yield('title', __('Admin panel'))</h1>

    {{-- O'ng tomon: foydalanuvchi menyusi --}}
    <div class="ml-auto" x-data="{ open: false }">
        <button @click="open = !open" class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-gray-100">
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">
                {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
            </span>
            <span class="hidden font-medium text-gray-700 sm:block">{{ auth()->user()->name }}</span>
            <span class="text-gray-400">▾</span>
        </button>

        <div x-show="open" x-cloak @click.outside="open = false" x-transition
             class="absolute right-4 mt-2 w-48 rounded-lg border border-gray-200 bg-white py-1 shadow-lg">
            <div class="border-b border-gray-100 px-4 py-2">
                <p class="truncate text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                <p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                    {{ __('Chiqish') }}
                </button>
            </form>
        </div>
    </div>
</header>
