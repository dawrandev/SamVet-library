@extends('layouts.site-auth')

@section('title', __('Kirish'))

@section('content')
    {{-- Brand --}}
    <a href="{{ route('home') }}" class="mb-7 flex items-center gap-3">
        <span class="flex h-14 w-14 flex-none items-center justify-center rounded-xl bg-white/95 p-1.5">
            <img src="{{ asset('images/samvet/logo.png') }}" alt="{{ __('SDVUNF Nukus filiali logotipi') }}"
                 class="h-full w-full object-contain" width="56" height="56" />
        </span>
        <span class="leading-tight">
            <span class="block text-base font-bold text-white">SDVUNF · {{ __('Nukus filiali') }}</span>
            <span class="block text-xs text-blue-100/70">{{ __('Axborot resurs markazi (ARM)') }}</span>
        </span>
    </a>

    {{-- Card --}}
    <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">
        <h1 class="text-2xl font-extrabold tracking-tight text-gray-900">{{ __('Kirish') }}</h1>
        <p class="mt-1.5 text-sm leading-relaxed text-gray-500">{{ __('Elektron resurslarni online o‘qish uchun hisobingizga kiring.') }}</p>

        @if (session('status'))
            <p class="mt-5 rounded-lg bg-amber-50 px-3 py-2.5 text-sm text-amber-800">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('reader.login') }}" class="mt-6 space-y-5">
            @csrf

            {{-- ID number --}}
            <div>
                <label for="id_number" class="text-sm font-semibold text-gray-900">{{ __('Login') }}</label>
                <div class="relative mt-2">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                    <input type="text" id="id_number" name="id_number" value="{{ old('id_number') }}"
                           autocomplete="username" autofocus required
                           placeholder="{{ __('ID raqamingiz, masalan BT0122001') }}"
                           @class([
                               'w-full rounded-lg border bg-gray-50 py-2.5 pl-9 pr-3 text-sm text-gray-800 placeholder:text-gray-400 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:outline-none',
                               'border-red-400 focus:border-red-500' => $errors->has('id_number'),
                               'border-gray-200 focus:border-blue-500' => ! $errors->has('id_number'),
                           ]) />
                </div>
                @error('id_number')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div x-data="{ show: false }">
                <label for="password" class="text-sm font-semibold text-gray-900">{{ __('Parol') }}</label>
                <div class="relative mt-2">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                    <input :type="show ? 'text' : 'password'" id="password" name="password"
                           autocomplete="current-password" required placeholder="••••••••"
                           @class([
                               'w-full rounded-lg border bg-gray-50 py-2.5 pl-9 pr-10 text-sm text-gray-800 placeholder:text-gray-400 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:outline-none',
                               'border-red-400 focus:border-red-500' => $errors->has('password'),
                               'border-gray-200 focus:border-blue-500' => ! $errors->has('password'),
                           ]) />
                    <button type="button" @click="show = !show" tabindex="-1"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded p-1 text-gray-400 hover:text-gray-600"
                            :aria-label="show ? '{{ __('Parolni yashirish') }}' : '{{ __('Parolni ko‘rsatish') }}'">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                    </button>
                </div>
                @error('password')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-800">
                {{ __('Kirish') }}
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
            </button>
        </form>

        {{-- Help --}}
        <div class="mt-6 flex gap-2.5 rounded-lg bg-blue-50 px-3 py-3">
            <svg class="h-4 w-4 flex-none text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
            <p class="text-xs leading-relaxed text-gray-600">{{ __('Login va parolni Axborot resurs markazidan olasiz.') }}</p>
        </div>
    </div>

    <p class="mt-7 text-xs text-blue-100/50">© {{ date('Y') }} {{ __('Axborot resurs markazi') }}</p>
@endsection
