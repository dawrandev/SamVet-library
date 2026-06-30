<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', __('Admin panel')) — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 text-gray-800 antialiased">
    <div x-data="{ sidebarOpen: false }" class="flex h-full">

        @include('partials.admin.sidebar')

        {{-- Mobil uchun qorong'i fon (sidebar ochilganda) --}}
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
             class="fixed inset-0 z-20 bg-gray-900/50 lg:hidden"></div>

        <div class="flex min-w-0 flex-1 flex-col lg:pl-64">
            @include('partials.admin.header')

            <main class="flex-1 p-4 sm:p-6">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
