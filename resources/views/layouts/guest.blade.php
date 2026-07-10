<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/samvet/favicon-32.png') }}" />
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/samvet/favicon-192.png') }}" />
    <link rel="apple-touch-icon" href="{{ asset('images/samvet/apple-touch-icon.png') }}" />

    <title>@yield('title', __('Kirish')) — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    x-data="{ darkMode: false }"
    x-init="darkMode = JSON.parse(localStorage.getItem('darkMode'));
            $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{ 'dark bg-gray-900': darkMode === true }"
>
    @yield('content')
</body>
</html>
