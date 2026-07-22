@php
    $reader = $reader ?? null;
    $editing = ! is_null($reader);

    // Student types (so Alpine can swap labels) — matches ReaderType::isStudent()
    $studentTypes = collect($types)->filter(fn ($t) => $t->isStudent())->map(fn ($t) => $t->value)->values()->all();

    $curType = old('type', $reader?->type?->value);
    $curStatus = old('status', $reader?->status?->value);
    $curGender = old('gender', $reader?->gender?->value);
    $curAffiliationPlace = old('affiliation_place_id', $reader?->affiliation_place_id);
    $curAffiliationUnit = old('affiliation_unit_id', $reader?->affiliation_unit_id);
    $curAffiliationGroup = old('affiliation_group_id', $reader?->affiliation_group_id);
    $curRegion = old('region_id', $reader?->region_id);

    $base = 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90';
@endphp

<form
    method="POST"
    action="{{ $editing ? route('admin.readers.update', $reader) : route('admin.readers.store') }}"
    enctype="multipart/form-data"
    x-data="readerForm({
        studentTypes: @js($studentTypes),
        type: @js((string) $curType),
        districtsUrlTemplate: '{{ route('admin.regions.districts.lookup', ['region' => '__RID__']) }}',
        initialRegionId: @js($curRegion ? (int) $curRegion : null),
        initialDistrictId: @js(old('district_id', $reader?->district_id)),
    })"
    x-init="initDistricts()"
    @submit="submitUpload($event)"
>
    @csrf
    @if ($editing) @method('PUT') @endif

    <x-admin.form.upload-errors />
    <x-admin.form.uploading-overlay />

    {{-- Header + actions (sticky) --}}
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

    {{-- General error --}}
    @if ($errors->any())
        <x-alert type="error" class="mb-6">{{ __('Iltimos, formadagi xatolarni to‘g‘rilang.') }}</x-alert>
    @endif

    <div class="grid grid-cols-12 gap-6">
        {{-- LEFT: main --}}
        <div class="col-span-12 space-y-6 xl:col-span-8">
            <x-admin.form.section :title="__('Asosiy ma’lumotlar')">
                <div class="space-y-5">
                    <x-admin.form.input name="full_name" :label="__('F.I.SH')" :value="$reader?->full_name" required :placeholder="__('To‘liq ism sharif')" />

                    <div class="grid gap-5 sm:grid-cols-2">
                        {{-- Type (enum) --}}
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

                        {{-- Status (enum) --}}
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

            {{-- Affiliation --}}
            <x-admin.form.section :title="__('Mansublik')" :description="__('O‘qish yoki ish joyi ma’lumotlari')">
                <div class="grid gap-5 sm:grid-cols-3">
                    <div>
                        <label for="affiliation_place_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('O‘qish/ish joyi') }}</label>
                        <select name="affiliation_place_id" id="affiliation_place_id"
                                class="{{ $base }} {{ $errors->has('affiliation_place_id') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                            <option value="">{{ __('Tanlanmagan') }}</option>
                            @foreach ($affiliationPlaces as $place)
                                <option value="{{ $place->id }}" @selected((string) $curAffiliationPlace === (string) $place->id)>{{ $place->name }}</option>
                            @endforeach
                        </select>
                        @error('affiliation_place_id')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="affiliation_unit_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Mutaxassisligi/bo‘limi') }}</label>
                        <select name="affiliation_unit_id" id="affiliation_unit_id"
                                class="{{ $base }} {{ $errors->has('affiliation_unit_id') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                            <option value="">{{ __('Tanlanmagan') }}</option>
                            @foreach ($affiliationUnits as $unit)
                                <option value="{{ $unit->id }}" @selected((string) $curAffiliationUnit === (string) $unit->id)>{{ $unit->name }}</option>
                            @endforeach
                        </select>
                        @error('affiliation_unit_id')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="affiliation_group_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Guruhi/lavozimi') }}</label>
                        <select name="affiliation_group_id" id="affiliation_group_id"
                                class="{{ $base }} {{ $errors->has('affiliation_group_id') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                            <option value="">{{ __('Tanlanmagan') }}</option>
                            @foreach ($affiliationGroups as $group)
                                <option value="{{ $group->id }}" @selected((string) $curAffiliationGroup === (string) $group->id)>{{ $group->name }}</option>
                            @endforeach
                        </select>
                        @error('affiliation_group_id')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>
                </div>
            </x-admin.form.section>

            {{-- Personal --}}
            <x-admin.form.section :title="__('Shaxsiy ma’lumotlar')">
                <div class="space-y-5">
                    <div class="grid gap-5 sm:grid-cols-3">
                        <x-admin.form.input name="nationality" :label="__('Millati')" :value="$reader?->nationality" />
                        <x-admin.form.input name="birth_date" type="date" :label="__('Tug‘ilgan sana')" :value="$reader?->birth_date?->format('Y-m-d')" />
                        {{-- Gender (enum) --}}
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
                        <div>
                            <label for="region_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Viloyat') }}</label>
                            <select name="region_id" id="region_id" x-model="regionId" @change="pickRegion($event.target.value)"
                                    class="{{ $base }} {{ $errors->has('region_id') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                                <option value="">{{ __('Tanlanmagan') }}</option>
                                @foreach ($regions as $region)
                                    <option value="{{ $region->id }}">{{ $region->name }}</option>
                                @endforeach
                            </select>
                            @error('region_id')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="district_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Tuman') }}</label>
                            <select name="district_id" id="district_id" x-ref="districtSelect" x-model="districtId" :disabled="regionId === null || loadingDistricts"
                                    class="{{ $base }} {{ $errors->has('district_id') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                                <option value="" x-text="regionId === null ? '{{ __('Avval viloyatni tanlang') }}' : '{{ __('Tanlanmagan') }}'"></option>
                                <template x-for="d in districts" :key="d.id">
                                    <option :value="d.id" x-text="d.name"></option>
                                </template>
                            </select>
                            <p x-show="loadingDistricts" x-cloak class="mt-1.5 text-theme-xs text-gray-400">{{ __('Tumanlar yuklanmoqda...') }}</p>
                            @error('district_id')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-admin.form.input name="address" :label="__('Manzil')" :value="$reader?->address" />
                        <x-admin.form.input name="phone" :label="__('Telefon')" :value="$reader?->phone" :placeholder="'+998 __ ___ __ __'" />
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-admin.form.input name="member_year" type="number" :label="__('A‘zolik yili')" :value="$reader?->member_year" placeholder="2024" />
                    </div>
                </div>
            </x-admin.form.section>

            {{-- Additional --}}
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

        {{-- RIGHT: photo --}}
        <div class="col-span-12 space-y-6 xl:col-span-4">
            <x-admin.form.section :title="__('Rasm')">
                <x-admin.form.file name="photo" :image="true" accept="image/*" with-progress
                    :currentUrl="$reader?->photo ? asset('storage/' . $reader->photo) : null"
                    :help="__('JPG/PNG, 2 MB gacha')" />
            </x-admin.form.section>
        </div>
    </div>

</form>
