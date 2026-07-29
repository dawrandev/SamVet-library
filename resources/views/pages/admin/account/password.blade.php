@extends('layouts.admin')

@section('title', __('Parolni o‘zgartirish'))

@section('content')
    <div class="mx-auto max-w-xl">
        <h2 class="mb-6 text-xl font-bold text-gray-800 dark:text-white/90">{{ __('Parolni o‘zgartirish') }}</h2>

        @if (session('success'))
            <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
            <form method="POST" action="{{ route('admin.password.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <x-admin.form.input name="current_password" type="password" :label="__('Joriy parol')" required />
                <x-admin.form.input name="password" type="password" :label="__('Yangi parol')" required
                    :help="__('Kamida 10 belgi.')" />
                <x-admin.form.input name="password_confirmation" type="password" :label="__('Yangi parolni tasdiqlang')" required />

                <button type="submit" class="bg-brand-500 hover:bg-brand-600 rounded-lg px-5 py-2.5 text-sm font-medium text-white">
                    {{ __('Saqlash') }}
                </button>
            </form>
        </div>
    </div>
@endsection
