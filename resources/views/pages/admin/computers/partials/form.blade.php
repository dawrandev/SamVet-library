@php
    $computer = $computer ?? null;
    $editing = ! is_null($computer);

    $typeOptions = \App\Enums\ComputerType::cases();
    $currentType = old('type', $editing ? $computer->type?->value : null);

    $statusOptions = \App\Enums\ComputerStatus::cases();
    $currentStatus = old('status', $editing ? $computer->status?->value : \App\Enums\ComputerStatus::Working->value);
@endphp

<form
    method="POST"
    action="{{ $editing ? route('admin.computers.update', $computer) : route('admin.computers.store') }}"
>
    @csrf
    @if ($editing) @method('PUT') @endif

    {{-- Header + actions (sticky) --}}
    <div class="sticky top-16 z-9 -mx-4 mb-6 flex items-center justify-between border-b border-gray-200 bg-gray-50/90 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 dark:border-gray-800 dark:bg-gray-900/90">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.computers.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">
                {{ $editing ? __('Kompyuterni tahrirlash') : __('Yangi kompyuter') }}
            </h2>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.computers.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</a>
            <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 rounded-lg px-5 py-2 text-sm font-medium text-white transition">{{ __('Saqlash') }}</button>
        </div>
    </div>

    {{-- General error --}}
    @if ($errors->any())
        <x-alert type="error" class="mb-6">{{ __('Iltimos, formadagi xatolarni to‘g‘rilang.') }}</x-alert>
    @endif

    <div class="mx-auto max-w-3xl">
        <x-admin.form.section :title="__('Kompyuter ma’lumotlari')">
            <div class="space-y-5">
                <x-admin.form.input name="model" :label="__('Modeli')" :value="$computer?->model" required :placeholder="__('masalan: HP ProDesk 400 G7')" />

                <div class="grid gap-5 sm:grid-cols-2">
                    {{-- Type --}}
                    <div>
                        <label for="type" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Turi') }}<span class="text-error-500">*</span></label>
                        <select name="type" id="type" required
                                class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 {{ $errors->has('type') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                            <option value="">{{ __('Tanlang') }}</option>
                            @foreach ($typeOptions as $opt)
                                <option value="{{ $opt->value }}" @selected($currentType === $opt->value)>{{ $opt->label() }}</option>
                            @endforeach
                        </select>
                        @error('type')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Status --}}
                    <div>
                        <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Holati') }}<span class="text-error-500">*</span></label>
                        <select name="status" id="status" required
                                class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 {{ $errors->has('status') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                            @foreach ($statusOptions as $opt)
                                <option value="{{ $opt->value }}" @selected($currentStatus === $opt->value)>{{ $opt->label() }}</option>
                            @endforeach
                        </select>
                        @error('status')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.form.input name="inventory_number" :label="__('Inventar raqami')" :value="$computer?->inventory_number" required :placeholder="__('masalan: KMP-001')"
                        :help="__('Ochiq saytda ko‘rinmaydi.')" />

                    <x-admin.form.select name="location_id" :label="__('Joylashuv')" :options="$locations" :selected="$computer?->location_id" :placeholder="__('Tanlang')"
                        creatable create-translatable create-type="location" :create-label="__('Yangi joylashuv')" />
                </div>

                <x-admin.form.textarea name="note" :label="__('Eslatma')" :value="$computer?->note" :rows="3" />
            </div>
        </x-admin.form.section>
    </div>
</form>
