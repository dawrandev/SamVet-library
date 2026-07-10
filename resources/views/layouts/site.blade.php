<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
          font: 100,
          contrast: false,
          init() {
              this.font = Number(localStorage.getItem('a11yFont')) || 100;
              this.contrast = localStorage.getItem('a11yContrast') === '1';
              this.$watch('font', (v) => localStorage.setItem('a11yFont', v));
              this.$watch('contrast', (v) => localStorage.setItem('a11yContrast', v ? '1' : '0'));
          },
          zoom(step) { this.font = Math.min(130, Math.max(90, this.font + step)); },
      }"
      :style="`font-size: ${font}%`"
      :class="{ 'a11y-contrast': contrast }">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/samvet/favicon-32.png') }}" />
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/samvet/favicon-192.png') }}" />
    <link rel="apple-touch-icon" href="{{ asset('images/samvet/apple-touch-icon.png') }}" />

    <title>@yield('title', __('Axborot resurs markazi')) — {{ config('app.name') }}</title>
    <meta name="description" content="@yield('meta_description', __('Samarqand davlat veterinariya meditsinasi, chorvachilik va biotexnologiyalar universiteti Nukus filiali Axborot resurs markazi elektron kutubxonasi.'))" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-outfit min-h-screen bg-gray-50 text-gray-700 antialiased">
    @include('partials.site.topbar')
    @include('partials.site.header')

    <main>
        @yield('content')
    </main>

    @include('partials.site.footer')
</body>
</html>
