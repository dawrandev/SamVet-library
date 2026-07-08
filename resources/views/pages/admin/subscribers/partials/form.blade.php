@php
    $subscriber = $subscriber ?? null;
    $editing = ! is_null($subscriber);
@endphp

<form
    method="POST"
    action="{{ $editing ? route('admin.subscribers.update', $subscriber) : route('admin.subscribers.store') }}"
>
    @csrf
    @if ($editing) @method('PUT') @endif

    {{-- Header + actions (sticky) --}}
    <div class="sticky top-16 z-9 -mx-4 mb-6 flex items-center justify-between border-b border-gray-200 bg-gray-50/90 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 dark:border-gray-800 dark:bg-gray-900/90">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.subscribers.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">
                {{ $editing ? __('Obunachini tahrirlash') : __('Yangi obunachi') }}
            </h2>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.subscribers.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</a>
            <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 rounded-lg px-5 py-2 text-sm font-medium text-white transition">{{ __('Saqlash') }}</button>
        </div>
    </div>

    {{-- General error --}}
    @if ($errors->any())
        <x-alert type="error" class="mb-6">{{ __('Iltimos, formadagi xatolarni to‘g‘rilang.') }}</x-alert>
    @endif

    <div class="mx-auto max-w-2xl">
        <x-admin.form.section :title="__('Obunachi ma’lumotlari')">
            <div class="space-y-5">
                <x-admin.form.input name="full_name" :label="__('F.I.SH')" :value="$subscriber?->full_name" required :placeholder="__('To‘liq ism sharif')" />

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.form.input name="position" :label="__('Lavozimi')" :value="$subscriber?->position" :placeholder="__('masalan: dotsent')" />
                    <x-admin.form.input name="department" :label="__('Bo‘limi')" :value="$subscriber?->department" :placeholder="__('bo‘lim / kafedra')" />
                </div>

                <x-admin.form.input name="phone" :label="__('Telefon')" :value="$subscriber?->phone" :placeholder="'+998 __ ___ __ __'" />
            </div>
        </x-admin.form.section>
    </div>
</form>
