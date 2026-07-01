@extends('layouts.admin')

@section('title', __('Bosh sahifa'))

@section('content')
    <div class="grid grid-cols-12 gap-4 md:gap-6">
        {{-- Chap ustun --}}
        <div class="col-span-12 space-y-6 xl:col-span-7">
            {{-- Metrik kartalar --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6">
                <x-admin.metric-card :label="__('Jami kitoblar')" value="0" icon="📕" :trend="null" />
                <x-admin.metric-card :label="__('Kategoriyalar')" value="0" icon="🗂️" :trend="null" />
                <x-admin.metric-card :label="__('Foydalanuvchilar')" value="0" icon="👥" :trend="null" />
                <x-admin.metric-card :label="__('Jami ko‘rishlar')" value="0" icon="👁️" :trend="null" />
            </div>

            {{-- Bar chart: oylik ko'rishlar --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Oylik ko‘rishlar') }}</h3>
                </div>
                <div class="custom-scrollbar max-w-full overflow-x-auto">
                    <div class="-ml-5 min-w-[650px] pl-2 xl:min-w-full">
                        <div id="chartOne" class="-ml-5 h-full min-w-[650px] pl-2 xl:min-w-full"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- O'ng ustun: radial (maqsad) --}}
        <div class="col-span-12 xl:col-span-5">
            <div class="rounded-2xl border border-gray-200 bg-gray-100 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="shadow-default rounded-2xl bg-white px-5 pt-5 pb-11 dark:bg-gray-900 sm:px-6 sm:pt-6">
                    <div class="flex justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Oylik maqsad') }}</h3>
                            <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">{{ __('Har oy uchun belgilangan maqsad') }}</p>
                        </div>
                    </div>
                    <div class="relative max-h-[195px]">
                        <div id="chartTwo" class="h-full"></div>
                        <span class="bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500 absolute left-1/2 top-[85%] -translate-x-1/2 -translate-y-[85%] rounded-full px-3 py-1 text-xs font-medium">+10%</span>
                    </div>
                    <p class="mx-auto mt-1.5 w-full max-w-[380px] text-center text-sm text-gray-500 sm:text-base">
                        {{ __('Bugun kutubxona faolligi o‘tgan oyга nisbatan yuqori. Ajoyib natija!') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- To'liq kenglik: area chart --}}
        <div class="col-span-12">
            <div class="rounded-2xl border border-gray-200 bg-white px-5 pt-5 pb-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
                <div class="mb-6 flex flex-col gap-5 sm:flex-row sm:justify-between">
                    <div class="w-full">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Statistika') }}</h3>
                        <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">{{ __('Ko‘rishlar va o‘qishlar dinamikasi') }}</p>
                    </div>

                    <div class="flex w-full items-start gap-3 sm:justify-end">
                        <div x-data="{ selected: 'overview' }" class="inline-flex w-fit items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-gray-900">
                            <button @click="selected = 'overview'"
                                :class="selected === 'overview' ? 'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' : 'text-gray-500 dark:text-gray-400'"
                                class="text-theme-sm rounded-md px-3 py-2 font-medium hover:text-gray-900 dark:hover:text-white">{{ __('Umumiy') }}</button>
                            <button @click="selected = 'views'"
                                :class="selected === 'views' ? 'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' : 'text-gray-500 dark:text-gray-400'"
                                class="text-theme-sm rounded-md px-3 py-2 font-medium hover:text-gray-900 dark:hover:text-white">{{ __('Ko‘rishlar') }}</button>
                            <button @click="selected = 'reads'"
                                :class="selected === 'reads' ? 'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' : 'text-gray-500 dark:text-gray-400'"
                                class="text-theme-sm rounded-md px-3 py-2 font-medium hover:text-gray-900 dark:hover:text-white">{{ __('O‘qishlar') }}</button>
                        </div>

                        <div class="relative max-w-41">
                            <input class="datepicker text-theme-sm shadow-theme-xs h-10 w-full rounded-lg border border-gray-200 bg-white py-2.5 pr-4 pl-[34px] font-medium text-gray-700 focus:ring-0 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
                                placeholder="{{ __('Sanani tanlang') }}" data-class="flatpickr-right" readonly />
                            <div class="pointer-events-none absolute inset-0 right-auto left-3 flex items-center">
                                <svg class="fill-gray-700 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M6.66683 1.54199C7.08104 1.54199 7.41683 1.87778 7.41683 2.29199V3.00033H12.5835V2.29199C12.5835 1.87778 12.9193 1.54199 13.3335 1.54199C13.7477 1.54199 14.0835 1.87778 14.0835 2.29199V3.00033L15.4168 3.00033C16.5214 3.00033 17.4168 3.89576 17.4168 5.00033V7.50033V15.8337C17.4168 16.9382 16.5214 17.8337 15.4168 17.8337H4.5835C3.47893 17.8337 2.5835 16.9382 2.5835 15.8337V7.50033V5.00033C2.5835 3.89576 3.47893 3.00033 4.5835 3.00033L5.91683 3.00033V2.29199C5.91683 1.87778 6.25262 1.54199 6.66683 1.54199ZM6.66683 4.50033H4.5835C4.30735 4.50033 4.0835 4.72418 4.0835 5.00033V6.75033H15.9168V5.00033C15.9168 4.72418 15.693 4.50033 15.4168 4.50033H13.3335H6.66683ZM15.9168 8.25033H4.0835V15.8337C4.0835 16.1098 4.30735 16.3337 4.5835 16.3337H15.4168C15.693 16.3337 15.9168 16.1098 15.9168 15.8337V8.25033Z" fill="" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="custom-scrollbar max-w-full overflow-x-auto">
                    <div id="chartThree" class="-ml-4 min-w-[700px] pl-2"></div>
                </div>
            </div>
        </div>
    </div>
@endsection
