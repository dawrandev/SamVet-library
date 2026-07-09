@php
    $menuItem = $menuItem ?? null;
    $editing = ! is_null($menuItem);

    $titleValue = $editing ? $menuItem->getTranslations('title') : [];
    $preParentId = $editing ? $menuItem->parent_id : ($selectedParentId ?? null);
    $preUrl = $menuItem?->url;
    $preSortOrder = $menuItem?->sort_order;
    $preIsActive = $editing ? $menuItem->is_active : true;
    $preTargetBlank = $editing ? $menuItem->target_blank : false;

    $currentParent = old('parent_id', $preParentId);
    $curType = old('type', $editing ? $menuItem->type->value : \App\Enums\MenuItemType::Dropdown->value);
@endphp

<form
    method="POST"
    action="{{ $editing ? route('admin.menu-items.update', $menuItem) : route('admin.menu-items.store') }}"
>
    @csrf
    @if ($editing) @method('PUT') @endif

    {{-- Header --}}
    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('admin.menu-items.index') }}"
           class="flex h-9 w-9 flex-none items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
        <div>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">
                {{ $editing ? __('Menyuni tahrirlash') : __('Yangi menyu') }}
            </h2>
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Sayt navbar menyusi elementi') }}</p>
        </div>
    </div>

    @if ($errors->any())
        <x-alert type="error" class="mb-6">{{ __('Iltimos, formadagi xatolarni to‘g‘rilang.') }}</x-alert>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3" x-data="{ type: '{{ $curType }}' }">
        {{-- Left: main info --}}
        <div class="space-y-6 lg:col-span-2">
            <x-admin.form.section :title="__('Sarlavha va turi')">
                <div class="space-y-5">
                    <x-admin.form.translatable-input name="title" :label="__('Sarlavha')" :value="$titleValue"
                        :placeholders="['uz' => __('masalan: Bosh sahifa'), 'ru' => __('например: Главная'), 'kk' => __('mısalı: Bas bet')]" />

                    {{-- Type --}}
                    <div>
                        <label for="type" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Turi') }}<span class="text-error-500">*</span></label>
                        <select name="type" id="type" x-model="type"
                                class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 {{ $errors->has('type') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                            @foreach (\App\Enums\MenuItemType::cases() as $t)
                                <option value="{{ $t->value }}">{{ $t->label() }}</option>
                            @endforeach
                        </select>
                        @error('type')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                        <p class="mt-1 text-theme-xs text-gray-400">
                            <span x-show="type === 'dropdown'">{{ __('Ochiluvchi menyu — ichida bolalar bo‘ladi, havola yo‘q.') }}</span>
                            <span x-show="type === 'page'" x-cloak>{{ __('Kontent sahifa — saqlagach «Sahifa» tugmasi orqali matn yoziladi.') }}</span>
                            <span x-show="type === 'module'" x-cloak>{{ __('Mavjud bo‘lim (katalog, jurnal, yangilik) — havolani ko‘rsating.') }}</span>
                            <span x-show="type === 'external'" x-cloak>{{ __('Tashqi sayt — to‘liq URL kiriting.') }}</span>
                        </p>
                    </div>

                    {{-- URL (only for module/external) --}}
                    <div x-show="type === 'module' || type === 'external'" x-cloak>
                        <x-admin.form.input name="url" :label="__('Havola')" :value="$preUrl"
                            :placeholder="__('masalan: /katalog yoki https://...')" />
                    </div>
                </div>
            </x-admin.form.section>
        </div>

        {{-- Right: placement + settings --}}
        <div class="space-y-6 lg:col-span-1">
            <x-admin.form.section :title="__('Joylashuv')">
                <div class="space-y-5">
                    {{-- Parent menu (id + label array — manual) --}}
                    <div>
                        <label for="parent_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Ota menyu') }}</label>
                        <select name="parent_id" id="parent_id"
                                class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 {{ $errors->has('parent_id') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                            <option value="">{{ __('— Yuqori daraja —') }}</option>
                            @foreach ($parents as $parent)
                                <option value="{{ $parent['id'] }}" @selected((string) $currentParent === (string) $parent['id'])>{{ $parent['label'] }}</option>
                            @endforeach
                        </select>
                        @error('parent_id')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@else
                            <p class="mt-1 text-theme-xs text-gray-400">{{ __('Bo‘sh — yuqori daraja menyu.') }}</p>
                        @enderror
                    </div>

                    <x-admin.form.input name="sort_order" type="number" :label="__('Tartib')" :value="$preSortOrder"
                        :placeholder="'0'" :help="__('Bo‘sh qoldirilsa avtomatik oxiriga qo‘yiladi.')" />
                </div>
            </x-admin.form.section>

            <x-admin.form.section :title="__('Sozlamalar')">
                <div class="space-y-4">
                    <x-admin.form.switch name="is_active" :label="__('Faol')" :checked="$preIsActive"
                        :help="__('Faol bo‘lmagan menyular saytda ko‘rinmaydi.')" />
                    <x-admin.form.switch name="target_blank" :label="__('Yangi oynada ochilsin')" :checked="$preTargetBlank"
                        :help="__('Tashqi havolalar uchun qulay.')" />
                </div>
            </x-admin.form.section>
        </div>
    </div>

    {{-- Actions --}}
    <div class="mt-6 flex items-center justify-end gap-3 border-t border-gray-100 pt-5 dark:border-gray-800">
        <a href="{{ route('admin.menu-items.index') }}"
           class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</a>
        <button type="submit"
                class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 rounded-lg px-6 py-2.5 text-sm font-medium text-white transition">{{ __('Saqlash') }}</button>
    </div>
</form>
