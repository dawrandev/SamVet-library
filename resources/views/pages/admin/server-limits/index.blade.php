@extends('layouts.admin')

@section('title', __('Server limitlari'))

@section('content')
    {{-- Title --}}
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ __('Server limitlari') }}</h2>
        <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">
            {{ __('Ilova haqiqatda qanday cheklovlar ostida ishlayotgani. Bu qiymatlar serverdagi `php -i` natijasidan farq qilishi mumkin — bu sahifa veb so‘rov ichida o‘qiydi, ya‘ni haqiqiy ishlaydigan sozlamani ko‘rsatadi.') }}
        </p>
    </div>

    {{-- Verdict --}}
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Xulosa') }}</h3>

        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Server qabul qiladigan eng katta fayl') }}</dt>
                <dd class="mt-1 text-lg font-bold text-gray-800 dark:text-white/90">
                    {{ App\Services\ServerLimitsService::format($verdict['effective_upload']) }}
                </dd>
            </div>
            <div>
                <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Ilova ruxsat berayotgan hajm') }}</dt>
                <dd class="mt-1 text-lg font-bold text-gray-800 dark:text-white/90">
                    {{ App\Services\ServerLimitsService::format($verdict['app_allows']) }}
                </dd>
            </div>
            <div>
                <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Bir so‘rovga xotira') }}</dt>
                <dd class="mt-1 text-lg font-bold text-gray-800 dark:text-white/90">
                    {{ App\Services\ServerLimitsService::format($verdict['memory']) }}
                </dd>
            </div>
        </dl>

        @if ($verdict['mismatch'])
            <x-alert type="error" class="mt-5">
                {{ __('Ilova serverdan kattaroq faylga ruxsat beryapti. Bunday fayl yuklansa, Laravel unga umuman yetib bormaydi — so‘rov PHP darajasida uzilib, tushunarli xato o‘rniga 503 chiqadi.') }}
            </x-alert>
        @endif

        <p class="text-theme-sm mt-4 text-gray-500 dark:text-gray-400">
            {{ __('PHP interfeysi (SAPI)') }}: <span class="font-mono font-semibold">{{ $verdict['sapi'] }}</span>
            <span class="ml-1">{{ __('— “cli” bo‘lsa bu sahifa noto‘g‘ri joyda ochilgan; brauzerda ochilganda “fpm-fcgi” yoki shunga o‘xshash bo‘lishi kerak.') }}</span>
        </p>
    </div>

    {{-- Upload / memory settings --}}
    <div class="mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Yuklash va xotira') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px]">
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach ($uploadLimits as $row)
                        <tr>
                            <td class="px-6 py-3 font-mono text-sm text-gray-700 dark:text-gray-300">{{ $row['key'] }}</td>
                            <td class="px-6 py-3 font-mono text-sm font-bold text-gray-800 dark:text-white/90">{{ $row['value'] }}</td>
                            <td class="text-theme-sm px-6 py-3 text-gray-500 dark:text-gray-400">{{ $row['note'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- OPcache --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('OPcache') }}</h3>
            <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">
                {{ __('Deploy’dan keyin yangi kod darrov ishlaydimi — shu yerdan ko‘rinadi.') }}
            </p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px]">
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach ($opcache as $row)
                        <tr>
                            <td class="px-6 py-3 font-mono text-sm text-gray-700 dark:text-gray-300">{{ $row['key'] }}</td>
                            <td class="px-6 py-3 font-mono text-sm font-bold text-gray-800 dark:text-white/90">{{ $row['value'] }}</td>
                            <td class="text-theme-sm px-6 py-3 text-gray-500 dark:text-gray-400">{{ $row['note'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
