@php
    $avtoreferat = $avtoreferat ?? null;
    $editing = ! is_null($avtoreferat);

    $degreeOptions = \App\Enums\DissertationDegree::cases();
    $currentDegree = old('degree', $editing ? $avtoreferat->degree?->value : null);

    $conditionOptions = \App\Enums\CopyCondition::cases();
    $currentCondition = old('condition', $editing ? $avtoreferat->condition?->value : null);
@endphp

<form
    method="POST"
    action="{{ $editing ? route('admin.avtoreferats.update', $avtoreferat) : route('admin.avtoreferats.store') }}"
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
            <a href="{{ route('admin.avtoreferats.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">
                {{ $editing ? __('Avtoreferatni tahrirlash') : __('Yangi avtoreferat') }}
            </h2>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.avtoreferats.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</a>
            <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 rounded-lg px-5 py-2 text-sm font-medium text-white transition">{{ __('Saqlash') }}</button>
        </div>
    </div>

    {{-- General error --}}
    @if ($errors->any())
        <x-alert type="error" class="mb-6">{{ __('Iltimos, formadagi xatolarni to‘g‘rilang.') }}</x-alert>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Left: main details --}}
        <div class="space-y-6">
            <x-admin.form.section :title="__('Asosiy ma’lumotlar')">
                <div class="space-y-5">
                    <x-admin.form.input name="title" :label="__('Avtoreferat nomi')" :value="$avtoreferat?->title" required :placeholder="__('Avtoreferat nomi')" />
                    <x-admin.form.input name="author" :label="__('Muallifi')" :value="$avtoreferat?->author" :placeholder="__('masalan: Aliyev A.')" />

                    <x-admin.form.input name="specialty" :label="__('Ixtisoslik shifri va nomi')" :value="$avtoreferat?->specialty" :placeholder="__('masalan: 05.07.01 – ...')" />

                    <x-admin.form.select name="science_field_id" :label="__('Fan nomi')" :options="$scienceFields" :selected="$avtoreferat?->science_field_id" :placeholder="__('Tanlang')"
                        creatable create-type="science_field" :create-label="__('Yangi fan')" />

                    <div>
                        <label for="degree" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Turi') }}</label>
                        <select name="degree" id="degree"
                                class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 {{ $errors->has('degree') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                            <option value="">{{ __('Tanlang') }}</option>
                            @foreach ($degreeOptions as $opt)
                                <option value="{{ $opt->value }}" @selected($currentDegree === $opt->value)>{{ $opt->label() }}</option>
                            @endforeach
                        </select>
                        @error('degree')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>

                    <x-admin.form.input name="advisor" :label="__('Ilmiy rahbar')" :value="$avtoreferat?->advisor" required :placeholder="__('masalan: Aliyev A.')" />
                </div>
            </x-admin.form.section>

            <x-admin.form.section :title="__('Dissertatsiya himoyasi')">
                <div class="space-y-5">
                    <x-admin.form.input name="council_number" :label="__('Ilmiy kengash raqami')" :value="$avtoreferat?->council_number" />
                    <x-admin.form.input name="defense_institution" :label="__('Dissertatsiya himoya muassasi')" :value="$avtoreferat?->defense_institution" />
                    <x-admin.form.input name="performed_institution" :label="__('Dissertatsiya bajarilgan muassasi')" :value="$avtoreferat?->performed_institution" />
                </div>
            </x-admin.form.section>
        </div>

        {{-- Right: bibliographic + extras --}}
        <div class="space-y-6">
            <x-admin.form.section :title="__('Bibliografik ma’lumotlar')">
                <div class="space-y-5">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-admin.form.input name="udc" :label="__('UO‘K')" :value="$avtoreferat?->udc" placeholder="masalan: 330.1" />
                        <x-admin.form.input name="registration_number" :label="__('Ro‘yxat raqami')" :value="$avtoreferat?->registration_number" />
                    </div>

                    <div>
                        <label for="condition" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Holati') }}</label>
                        <select name="condition" id="condition"
                                class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 {{ $errors->has('condition') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                            <option value="">{{ __('Tanlang') }}</option>
                            @foreach ($conditionOptions as $opt)
                                <option value="{{ $opt->value }}" @selected($currentCondition === $opt->value)>{{ $opt->label() }}</option>
                            @endforeach
                        </select>
                        @error('condition')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-admin.form.select name="publication_place_id" :label="__('Nashr joyi')" :options="$publicationPlaces" :selected="$avtoreferat?->publication_place_id" :placeholder="__('Tanlang')"
                            creatable create-translatable create-type="publication_place" :create-label="__('Yangi nashr joyi')" />
                        <x-admin.form.input type="number" name="defense_year" :label="__('Himoya yili')" :value="$avtoreferat?->defense_year" placeholder="masalan: 2024" />
                    </div>

                    <x-admin.form.input name="inventory_number" :label="__('Inventari')" :value="$avtoreferat?->inventory_number" />

                    <x-admin.form.multiselect name="language_ids" :label="__('Tillari')"
                        :options="$languages->map(fn ($l) => ['id' => $l->id, 'label' => $l->name])"
                        :selected="$avtoreferat?->languages->pluck('id')->all()" :placeholder="__('Tanlang')"
                        :help="__('Avtoreferat bir nechta tilda bo‘lishi mumkin.')" />
                </div>
            </x-admin.form.section>

            <x-admin.form.section :title="__('Qo’shimcha')">
                <div class="space-y-5">
                    <x-admin.form.textarea name="annotation" :label="__('Annotatsiya')" :value="$avtoreferat?->annotation" :rows="4" :placeholder="__('Avtoreferat annotatsiyasi')" />

                    <x-admin.form.input name="keywords" :label="__('Tayanch so‘zlar')" :value="$avtoreferat?->keywords" :placeholder="__('vergul bilan ajratib yozing')" />

                    <x-admin.form.file name="electronic_file" :label="__('Elektron fayl (PDF)')" accept="application/pdf" with-progress
                        :currentName="$avtoreferat?->electronic_file ? __('Fayl mavjud') : null"
                        :help="$avtoreferat?->electronic_file ? __('Yangi fayl yuklasangiz eskisi almashtiriladi') : __('PDF, 950 MB gacha')" />
                </div>
            </x-admin.form.section>
        </div>
    </div>
</form>
