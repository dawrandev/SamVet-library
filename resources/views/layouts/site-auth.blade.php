<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="robots" content="noindex" />

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/samvet/favicon-32.png') }}" />
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/samvet/favicon-192.png') }}" />
    <link rel="apple-touch-icon" href="{{ asset('images/samvet/apple-touch-icon.png') }}" />

    <title>@yield('title') — {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-outfit min-h-screen bg-blue-900 text-gray-700 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12">
        @yield('content')
    </div>
</body>
</html>
