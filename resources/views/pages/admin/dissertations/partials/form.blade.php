@php
    $dissertation = $dissertation ?? null;
    $editing = ! is_null($dissertation);
    $selectedDegree = old('degree', $dissertation?->degree?->value);
@endphp

<form
    method="POST"
    action="{{ $editing ? route('admin.dissertations.update', $dissertation) : route('admin.dissertations.store') }}"
    enctype="multipart/form-data"
    x-data="uploadForm"
    @submit="submitUpload($event)"
>
    @csrf
    @if ($editing) @method('PUT') @endif

    <x-admin.form.upload-errors />
    <x-admin.form.uploading-overlay />

    {{-- Header + actions (sticky) --}}
    <div class="sticky top-16 z-9 -mx-4 mb-6 flex items-center justify-between border-b border-gray-200 bg-gray-50/90 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 dark:border-gray-800 dark:bg-gray-900/90">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.dissertations.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">
                {{ $editing ? __('Dissertatsiyani tahrirlash') : __('Yangi dissertatsiya') }}
            </h2>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dissertations.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</a>
            <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 rounded-lg px-5 py-2 text-sm font-medium text-white transition">{{ __('Saqlash') }}</button>
        </div>
    </div>

    {{-- General error --}}
    @if ($errors->any())
        <x-alert type="error" class="mb-6">{{ __('Iltimos, formadagi xatolarni to‘g‘rilang.') }}</x-alert>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Left: main details + degree --}}
        <div class="space-y-6">
            <x-admin.form.section :title="__('Dissertatsiya ma’lumotlari')">
                <div class="space-y-5">
                    <x-admin.form.input name="title" :label="__('Dissertatsiya nomi')" :value="$dissertation?->title" required :placeholder="__('Dissertatsiya nomi')" />
                    <x-admin.form.input name="author" :label="__('Muallifi')" :value="$dissertation?->author" :placeholder="__('masalan: Aliyev A.')" />

                    <x-admin.form.contributors-input :roles="$contributorRoles" :label="__('Boshqa ishtirokchilar')"
                        :value="$dissertation?->contributors->map(fn ($c) => ['contributor_role_id' => $c->contributor_role_id, 'name' => $c->name])"
                        :help="__('Muallif yo‘q yoki bo‘lsa ham — muharrir, tarjimon kabi boshqa ishtirokchilarni shu yerda qo‘shing.')" />
                </div>
            </x-admin.form.section>

            {{-- Degree-conditional fields: PhD/DSc fill Fan nomi + Ixtisoslik, Magistrlik fills Mutaxassislik. --}}
            <x-admin.form.section :title="__('Ilmiy darajasi')">
                <div class="space-y-5" x-data="{ degree: @js($selectedDegree ?? '') }">
                    <div>
                        <label for="degree" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Turi') }}</label>
                        <select name="degree" id="degree" x-model="degree"
                                class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 {{ $errors->has('degree') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                            <option value="">{{ __('Tanlang') }}</option>
                            @foreach ($degreeTypes as $opt)
                                <option value="{{ $opt->value }}">{{ $opt->label() }}</option>
                            @endforeach
                        </select>
                        @error('degree')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                        <p class="mt-1 text-theme-xs text-gray-400">{{ __('PhD/DSc bo‘lsa — Fan nomi va Ixtisoslik, Magistrlik bo‘lsa — Mutaxassislik to‘ldiriladi.') }}</p>
                    </div>

                    {{-- PhD/DSc-only fields --}}
                    <div x-show="degree === 'phd' || degree === 'dsc'" x-cloak class="space-y-5">
                        <x-admin.form.select name="science_field_id" :label="__('Fan nomi')" :options="$scienceFields" :selected="$dissertation?->science_field_id" :placeholder="__('Tanlang')"
                            creatable create-type="science_field" :create-label="__('Yangi fan')" />
                        <x-admin.form.select name="doctoral_specialty_id" :label="__('Ixtisoslik shifri va nomi')" :options="$doctoralSpecialties" :selected="$dissertation?->doctoral_specialty_id" :placeholder="__('Tanlang')"
                            creatable create-type="doctoral_specialty" :create-label="__('Yangi ixtisoslik')" />
                    </div>

                    {{-- Magistrlik-only field --}}
                    <div x-show="degree === 'master'" x-cloak class="space-y-5">
                        <x-admin.form.select name="master_specialty_id" :label="__('Mutaxassislik shifri va nomi')" :options="$masterSpecialties" :selected="$dissertation?->master_specialty_id" :placeholder="__('Tanlang')"
                            creatable create-type="master_specialty" :create-label="__('Yangi mutaxassislik')" />
                    </div>
                </div>
            </x-admin.form.section>
        </div>

        {{-- Right: bibliographic + inventory + extras --}}
        <div class="space-y-6">
            <x-admin.form.section :title="__('Bibliografik ma’lumotlar')">
                <div class="space-y-5">
                    <x-admin.form.input name="advisor" :label="__('Ilmiy rahbari')" :value="$dissertation?->advisor" :placeholder="__('masalan: Aliyev A.')" />
                    <x-admin.form.input name="institution" :label="__('Muassasi')" :value="$dissertation?->institution" />

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-admin.form.select name="language_id" :label="__('Tili')" :options="$languages" :selected="$dissertation?->language_id" :placeholder="__('Tanlang')"
                            creatable create-translatable create-type="language" :create-label="__('Yangi til')" />
                        <x-admin.form.select name="publication_place_id" :label="__('Nashr joyi')" :options="$publicationPlaces" :selected="$dissertation?->publication_place_id" :placeholder="__('Tanlang')"
                            creatable create-translatable create-type="publication_place" :create-label="__('Yangi nashr joyi')" />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-admin.form.input type="number" name="defense_year" :label="__('Himoya yili')" :value="$dissertation?->defense_year" placeholder="masalan: 2026" />
                        <x-admin.form.input type="number" name="pages" :label="__('Beti')" :value="$dissertation?->pages" />
                    </div>

                    <x-admin.form.input name="udc" :label="__('UO‘K')" :value="$dissertation?->udc" placeholder="masalan: 330.1" />
                </div>
            </x-admin.form.section>

            {{-- Admin-only fields — never shown on the client site. --}}
            <x-admin.form.section :title="__('Inventar (faqat admin)')">
                <div class="space-y-5">
                    <x-admin.form.input name="inventory_number" :label="__('Inventari')" :value="$dissertation?->inventory_number" />

                    <div>
                        <label for="condition" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Holati') }}</label>
                        <select name="condition" id="condition"
                                class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 {{ $errors->has('condition') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                            <option value="">{{ __('Tanlang') }}</option>
                            @foreach ($conditionOptions as $opt)
                                <option value="{{ $opt->value }}" @selected(old('condition', $dissertation?->condition?->value) === $opt->value)>{{ $opt->label() }}</option>
                            @endforeach
                        </select>
                        @error('condition')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>
                </div>
            </x-admin.form.section>

            <x-admin.form.section :title="__('Qo’shimcha')">
                <div class="space-y-5">
                    <x-admin.form.select name="resource_field_id" :label="__('Resurs sohasi')" :options="$resourceFields" :selected="$dissertation?->resource_field_id" :placeholder="__('Tanlang')"
                        creatable create-translatable create-type="resource_field" :create-label="__('Yangi soha')" />

                    <x-admin.form.textarea name="annotation" :label="__('Annotatsiya')" :value="$dissertation?->annotation" :rows="4" :placeholder="__('Dissertatsiya annotatsiyasi')" />

                    <x-admin.form.file name="electronic_file" :label="__('Elektron fayl (PDF)')" accept="application/pdf" with-progress
                        :currentName="$dissertation?->electronic_file ? __('Fayl mavjud') : null"
                        :help="$dissertation?->electronic_file ? __('Yangi fayl yuklasangiz eskisi almashtiriladi') : __('PDF, 950 MB gacha')" />
                </div>
            </x-admin.form.section>
        </div>
    </div>
</form>
