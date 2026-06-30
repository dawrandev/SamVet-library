@extends('layouts.guest')

@section('title', __('Tizimga kirish'))

@section('content')
    <div class="flex min-h-full">
        {{-- Chap tomon: brend paneli (faqat katta ekranlarda) --}}
        <div class="relative hidden w-1/2 flex-col justify-between bg-gradient-to-br from-indigo-600 to-indigo-800 p-12 text-white lg:flex">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 text-xl font-bold">📚</div>
                <span class="text-lg font-semibold">{{ config('app.name') }}</span>
            </div>

            <div class="max-w-md">
                <h1 class="text-3xl font-bold leading-tight">{{ __('Elektron kutubxona boshqaruv tizimi') }}</h1>
                <p class="mt-4 text-indigo-100">
                    {{ __('Kitoblar, kategoriyalar va foydalanuvchilarni boshqaring. Tizimga kirish uchun administrator bergan ma\'lumotlardan foydalaning.') }}
                </p>
            </div>

            <p class="text-sm text-indigo-200">
                &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('Barcha huquqlar himoyalangan.') }}
            </p>
        </div>

        {{-- O'ng tomon: login formasi --}}
        <div class="flex w-full flex-col justify-center px-6 py-12 lg:w-1/2 lg:px-16">
            <div class="mx-auto w-full max-w-sm">
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900">{{ __('Tizimga kirish') }}</h2>
                    <p class="mt-2 text-sm text-gray-500">{{ __('Admin panelga kirish uchun ma\'lumotlarni kiriting.') }}</p>
                </div>

                @if ($errors->any())
                    <x-alert type="error" class="mb-5">{{ $errors->first() }}</x-alert>
                @endif

                <form method="POST" action="{{ route('login') }}" x-data="{ showPassword: false }" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Email') }}</label>
                        <input
                            id="email" name="email" type="email" value="{{ old('email') }}"
                            required autofocus autocomplete="username" placeholder="admin@samvet.uz"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none @error('email') border-red-400 @enderror"
                        >
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Parol') }}</label>
                        <div class="relative">
                            <input
                                id="password" name="password" :type="showPassword ? 'text' : 'password'"
                                required autocomplete="current-password" placeholder="••••••••"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 pr-11 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none @error('password') border-red-400 @enderror"
                            >
                            <button type="button" @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600" tabindex="-1">
                                <span x-show="!showPassword" class="text-sm">👁</span>
                                <span x-show="showPassword" class="text-sm" x-cloak>🙈</span>
                            </button>
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500/30">
                        {{ __('Meni eslab qol') }}
                    </label>

                    <button type="submit"
                            class="flex w-full justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500/40 focus:outline-none">
                        {{ __('Kirish') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
