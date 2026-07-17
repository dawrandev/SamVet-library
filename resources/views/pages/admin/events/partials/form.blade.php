@php
    $event = $event ?? null;
    $editing = ! is_null($event);

    $curName = old('name', $event?->name);
    $curType = old('type', $event?->type?->value);
    $curDate = old('date', $event?->date?->format('Y-m-d'));
    $curNewsId = old('news_id', $event?->news_id);
    $curNote = old('note', $event?->note);
    $curLocationIds = old('location_ids', $editing ? $event->locations->pluck('id')->all() : []);

    $curParticipants = old('participants') ?? ($editing
        ? $event->participants->map(fn ($p) => [
            'is_external' => is_null($p->reader_id),
            'reader_id' => $p->reader_id,
            'reader_label' => $p->reader?->full_name,
            'external_name' => $p->external_name,
            'role' => $p->role->value,
        ])->all()
        : []);

    $locationOptions = $locations->map(fn ($l) => ['id' => $l->id, 'label' => $l->name])->all();
@endphp

<form
    method="POST"
    action="{{ $editing ? route('admin.events.update', $event) : route('admin.events.store') }}"
>
    @csrf
    @if ($editing) @method('PUT') @endif

    {{-- Header + actions (sticky) --}}
    <div class="sticky top-16 z-9 -mx-4 mb-6 flex items-center justify-between border-b border-gray-200 bg-gray-50/90 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 dark:border-gray-800 dark:bg-gray-900/90">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.events.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">
                {{ $editing ? __('Tadbirni tahrirlash') : __('Yangi tadbir') }}
            </h2>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.events.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</a>
            <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 rounded-lg px-5 py-2 text-sm font-medium text-white transition">{{ __('Saqlash') }}</button>
        </div>
    </div>

    @if ($errors->any())
        <x-alert type="error" class="mb-6">{{ __('Iltimos, formadagi xatolarni to‘g‘rilang.') }}</x-alert>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-admin.form.section :title="__('Tadbir haqida')">
            <div class="space-y-5">
                <x-admin.form.input name="name" :label="__('Nomi')" :value="$curName" required />

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Turi') }}<span class="text-error-500">*</span></label>
                        <select name="type" required
                                class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 {{ $errors->has('type') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                            <option value="">{{ __('Tanlang') }}</option>
                            @foreach ($types as $opt)
                                <option value="{{ $opt->value }}" @selected($curType === $opt->value)>{{ $opt->label() }}</option>
                            @endforeach
                        </select>
                        @error('type')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>
                    <x-admin.form.input type="date" name="date" :label="__('Sanasi')" :value="$curDate" required />
                </div>

                <x-admin.form.multiselect name="location_ids" :label="__('O‘tkazilgan joyi')" :options="$locationOptions" :selected="$curLocationIds"
                    creatable create-type="event_location" :create-label="__('Yangi joy')" :placeholder="__('Joy(lar)ni tanlang')" />

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Yangilik') }}</label>
                    <select name="news_id"
                            class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 {{ $errors->has('news_id') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700' }}">
                        <option value="">{{ __('Bog‘lanmagan') }}</option>
                        @foreach ($newsItems as $n)
                            <option value="{{ $n->id }}" @selected((string) $curNewsId === (string) $n->id)>{{ $n->title }}</option>
                        @endforeach
                    </select>
                    @error('news_id')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    <p class="mt-1 text-theme-xs text-gray-400">{{ __('Bog‘lansa, tadbirning havolasi shu yangilikning manziliga avtomatik teng bo‘ladi — qo‘lda yozilmaydi.') }}</p>
                </div>

                <x-admin.form.textarea name="note" :label="__('Izoh')" :value="$curNote" :rows="3" />
            </div>
        </x-admin.form.section>

        <x-admin.form.section :title="__('Ishtirokchilar')">
            <x-admin.form.event-participants-input :readers="$readers" :roles="$roles" :value="$curParticipants" />
        </x-admin.form.section>
    </div>
</form>
