<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', __('Admin panel')) — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    x-data="{ page: 'dashboard', loaded: true, darkMode: false, stickyMenu: false, sidebarToggle: false, scrollTop: false }"
    x-init="darkMode = JSON.parse(localStorage.getItem('darkMode'));
            $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{ 'dark bg-gray-900': darkMode === true }"
>
    @include('partials.admin.preloader')

    <div class="flex h-screen overflow-hidden">
        @include('partials.admin.sidebar')

        <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">
            @include('partials.admin.overlay')
            @include('partials.admin.header')

            <main>
                <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
                    @yield('content')
                </div>
            </main>
        </div>

        {{-- Delete confirmation modal (for all delete buttons) --}}
        <x-admin.confirm-delete />
    </div>
</body>
</html>
