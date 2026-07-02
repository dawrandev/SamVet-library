@php
    $reader = $reader ?? null;
    $editing = ! is_null($reader);

    // Talaba turlari (Alpine yorliqlarni almashtirishi uchun) — ReaderType::isStudent() bilan mos
    $studentTypes = collect($types)->filter(fn ($t) => $t->isStudent())->map(fn ($t) => $t->value)->values()->all();

    $curType = old('type', $reader?->type?->value);
    $curStatus = old('status', $reader?->status?->value);
    $curGender = old('gender', $reader?->gender?->value);

    $base = 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90';
@endphp

<form
    method="POST"
    action="{{ $editing ? route('admin.readers.update', $reader) : route('admin.readers.store') }}"
    enctype="multipart/form-data"
    x-data="{
        studentTypes: @js($studentTypes),
        type: @js((string) $curType),
        get isStudent() { return this.studentTypes.includes(this.type); },
    }"
>
    @csrf
    @if ($editing) @method('PUT') @endif

    {{-- Sarlavha + amallar (sticky) --}}
    <div class="sticky top-16 z-9 -mx-4 mb-6 flex items-center justify-between border-b border-gray-200 bg-gray-50/90 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 dark:border-gray-800 dark:bg-gray-900/90">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.readers.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">
                {{ $editing ? __('Foydalanuvchini tahrirlash') : __('Yangi foydalanuvchi') }}
            </h2>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.readers.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</a>
            <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 rounded-lg px-5 py-2 text-sm font-medium text-white transition">{{ __('Saqlash') }}</button>
        </div>
    </div>

    {{-- Umumiy xato --}}
    @if ($errors->any())
        <x-alert type="error" class="mb-6">{{ __('Iltimos, formadagi xatolarni to‘g‘rilang.') }}</x-alert>
    @endif

    <div class="grid grid-cols-12 gap-6">
        {{-- CHAP: asosiy --}}
        <div class="col-span-12 space-y-6 xl:col-span-8">
            <x-admin.form.section :title="__('Asosiy ma’lumotlar')">
                <div class="space-y-5">
                    <x-admin.form.input name="full_name" :label="__('F.I.SH')" :value="$reader?->full_name" required :placeholder="__('To‘liq ism sharif')" />

                    <div class="grid gap-5 sm:grid-cols-2">
                        {{-- Turi (enum) --}}
                        <div>
                            <label for="type" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Turi') }}<span class="text-error-500">*</span></label>
                            <select name="type" id="type" required x-model="type"
                                    class="{{ $base }} {{ $errors->has('type') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                                <option value="">{{ __('Tanlang') }}</option>
                                @foreach ($types as $t)
                                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                                @endforeach
                            </select>
                            @error('type')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                        </div>

                        {{-- Holati (enum) --}}
                        <div>
                            <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Holati') }}<span class="text-error-500">*</span></label>
                            <select name="status" id="status" required
                                    class="{{ $base }} {{ $errors->has('status') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                                @foreach ($statuses as $s)
                                    <option value="{{ $s->value }}" @selected($curStatus === $s->value)>{{ $s->label() }}</option>
                                @endforeach
                            </select>
                            @error('status')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </x-admin.form.section>

            {{-- Mansublik — yorliqlar Alpine bilan talaba/xodimga qarab o'zgaradi --}}
            <x-admin.form.section :title="__('Mansublik')" :description="__('O‘qish yoki ish joyi ma’lumotlari')">
                <div class="grid gap-5 sm:grid-cols-3">
                    <div>
                        <label for="affiliation_place" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
                               x-text="isStudent ? '{{ __('O‘qish joyi') }}' : '{{ __('Ish joyi') }}'"></label>
                        <input type="text" name="affiliation_place" id="affiliation_place" value="{{ old('affiliation_place', $reader?->affiliation_place) }}"
                               class="{{ $base }} {{ $errors->has('affiliation_place') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}" />
                        @error('affiliation_place')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="affiliation_unit" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
                               x-text="isStudent ? '{{ __('Mutaxassisligi') }}' : '{{ __('Bo‘limi') }}'"></label>
                        <input type="text" name="affiliation_unit" id="affiliation_unit" value="{{ old('affiliation_unit', $reader?->affiliation_unit) }}"
                               class="{{ $base }} {{ $errors->has('affiliation_unit') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}" />
                        @error('affiliation_unit')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="affiliation_group" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
                               x-text="isStudent ? '{{ __('Guruhi') }}' : '{{ __('Lavozimi') }}'"></label>
                        <input type="text" name="affiliation_group" id="affiliation_group" value="{{ old('affiliation_group', $reader?->affiliation_group) }}"
                               class="{{ $base }} {{ $errors->has('affiliation_group') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}" />
                        @error('affiliation_group')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>
                </div>
            </x-admin.form.section>

            {{-- Shaxsiy --}}
            <x-admin.form.section :title="__('Shaxsiy ma’lumotlar')">
                <div class="space-y-5">
                    <div class="grid gap-5 sm:grid-cols-3">
                        <x-admin.form.input name="nationality" :label="__('Millati')" :value="$reader?->nationality" />
                        <x-admin.form.input name="birth_date" type="date" :label="__('Tug‘ilgan sana')" :value="$reader?->birth_date?->format('Y-m-d')" />
                        {{-- Jinsi (enum) --}}
                        <div>
                            <label for="gender" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Jinsi') }}</label>
                            <select name="gender" id="gender"
                                    class="{{ $base }} {{ $errors->has('gender') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                                <option value="">{{ __('Tanlanmagan') }}</option>
                                @foreach ($genders as $g)
                                    <option value="{{ $g->value }}" @selected($curGender === $g->value)>{{ $g->label() }}</option>
                                @endforeach
                            </select>
                            @error('gender')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-admin.form.input name="passport" :label="__('Passport')" :value="$reader?->passport" :placeholder="__('masalan: AA1234567')" />
                        <x-admin.form.input name="pinfl" :label="__('JSHSHIR (PINFL)')" :value="$reader?->pinfl" />
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-admin.form.input name="district" :label="__('Tuman')" :value="$reader?->district" />
                        <x-admin.form.input name="phone" :label="__('Telefon')" :value="$reader?->phone" :placeholder="'+998 __ ___ __ __'" />
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-admin.form.input name="address" :label="__('Manzil')" :value="$reader?->address" />
                        <x-admin.form.input name="member_year" type="number" :label="__('A‘zolik yili')" :value="$reader?->member_year" placeholder="2024" />
                    </div>
                </div>
            </x-admin.form.section>

            {{-- Qo'shimcha --}}
            <x-admin.form.section :title="__('Qo‘shimcha ma’lumotlar')">
                <div class="space-y-5">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-admin.form.input name="registration_number" :label="__('Ro‘yxat raqami')" :value="$reader?->registration_number" />
                        <x-admin.form.input name="id_number" :label="__('ID raqami')" :value="$reader?->id_number" :help="__('Takrorlanmas bo‘lishi shart')" />
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-admin.form.input name="issued_date" type="date" :label="__('Berilgan sana')" :value="$reader?->issued_date?->format('Y-m-d')" />
                        <x-admin.form.input name="other_library_member" :label="__('Boshqa kutubxona a‘zosi')" :value="$reader?->other_library_member" />
                    </div>
                    <x-admin.form.textarea name="note" :label="__('Izoh')" :value="$reader?->note" :rows="4" :placeholder="__('Ichki eslatma...')" />
                </div>
            </x-admin.form.section>
        </div>

        {{-- O'NG: rasm --}}
        <div class="col-span-12 space-y-6 xl:col-span-4">
            <x-admin.form.section :title="__('Rasm')">
                <x-admin.form.file name="photo" :image="true" accept="image/*"
                    :currentUrl="$reader?->photo ? asset('storage/' . $reader->photo) : null"
                    :help="__('JPG/PNG, 2 MB gacha')" />
            </x-admin.form.section>
        </div>
    </div>

    {{-- Pastki saqlash --}}
    <div class="mt-6 flex justify-end gap-2">
        <a href="{{ route('admin.readers.index') }}" class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</a>
        <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 rounded-lg px-6 py-2.5 text-sm font-medium text-white transition">{{ __('Saqlash') }}</button>
    </div>
</form>
