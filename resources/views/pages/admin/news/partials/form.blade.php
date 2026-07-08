@php
    $news = $news ?? null;
    $editing = ! is_null($news);

    $titleVal = $editing ? $news->getTranslations('title') : [];
    $excerptVal = $editing ? $news->getTranslations('excerpt') : [];
    $bodyVal = $editing ? $news->getTranslations('body') : [];
    $curCategory = old('news_category_id', $news?->news_category_id);
    $curPublished = old('published_at', $editing ? $news->published_at?->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i'));
@endphp

<form method="POST"
      action="{{ $editing ? route('admin.news.update', $news) : route('admin.news.store') }}"
      enctype="multipart/form-data">
    @csrf
    @if ($editing) @method('PUT') @endif

    {{-- Title + actions --}}
    <div class="mb-6 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.news.index') }}" class="flex h-9 w-9 flex-none items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
            <div>
                <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">{{ $editing ? __('Yangilikni tahrirlash') : __('Yangi yangilik') }}</h2>
                <p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Kutubxona yangiligi — 3 tilda') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.news.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</a>
            <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 rounded-lg px-5 py-2 text-sm font-medium text-white transition">{{ __('Saqlash') }}</button>
        </div>
    </div>

    @if ($errors->any())
        <x-alert type="error" class="mb-6">{{ __('Iltimos, formadagi xatolarni to‘g‘rilang. Sarlavha va matn kamida bitta tilda to‘ldirilishi shart.') }}</x-alert>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Chap: asosiy kontent --}}
        <div class="space-y-6 lg:col-span-2">
            <x-admin.form.section :title="__('Kontent')">
                <div class="space-y-5">
                    <x-admin.form.translatable-tabs name="title" :label="__('Sarlavha')" :value="$titleVal" :required="true"
                        :placeholders="['uz' => __('Sarlavha (o‘zbekcha)'), 'ru' => __('Заголовок (рус)'), 'kk' => __('Sarlawḳa (qq)')]" />

                    <x-admin.form.translatable-tabs name="excerpt" :label="__('Qisqa matn (kartada ko‘rinadi)')" :value="$excerptVal" textarea :rows="2"
                        :placeholders="['uz' => __('Qisqacha...'), 'ru' => __('Кратко...'), 'kk' => __('Qısqasha...')]" />

                    <x-admin.form.rich-editor name="body" :label="__('Matn')" :value="$bodyVal"
                        :help="__('To‘liq mazmun. Til tablari orqali har tilga alohida yoziladi.')" />
                </div>
            </x-admin.form.section>
        </div>

        {{-- O'ng: nashr sozlamalari + media --}}
        <div class="space-y-6 lg:col-span-1">
            <x-admin.form.section :title="__('Nashr')">
                <div class="space-y-5">
                    <x-admin.form.select name="news_category_id" :label="__('Kategoriya')" :options="$categories"
                        :selected="$curCategory" :placeholder="__('Tanlang')" :required="true"
                        creatable create-translatable create-type="news_category" :create-label="__('Yangi kategoriya')" />

                    <x-admin.form.input type="datetime-local" name="published_at" :label="__('Nashr sanasi')" :value="$curPublished" />
                </div>
            </x-admin.form.section>

            <x-admin.form.section :title="__('Media')">
                <div class="space-y-5">
                    <x-admin.form.file name="cover" :label="__('Muqova rasmi')" :image="true" accept="image/jpeg,image/png,image/webp,image/gif"
                        :currentUrl="$editing && $news->cover_image ? asset('storage/' . $news->cover_image) : null"
                        :help="__('JPG/PNG/WebP, 2 MB gacha. Barcha tillar uchun umumiy.')" />

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Galereya (bir nechta rasm)') }}</label>
                        @if ($editing && $news->images->isNotEmpty())
                            <div class="mb-2 flex flex-wrap gap-2">
                                @foreach ($news->images as $img)
                                    <img src="{{ asset('storage/' . $img->path) }}" alt="" class="h-16 w-16 rounded-lg border border-gray-200 object-cover dark:border-gray-800" />
                                @endforeach
                            </div>
                        @endif
                        <input type="file" name="gallery[]" multiple accept="image/jpeg,image/png,image/webp,image/gif"
                               class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-600 hover:file:bg-brand-100 dark:text-gray-400 dark:file:bg-brand-500/15 dark:file:text-brand-400" />
                        <p class="mt-1 text-theme-xs text-gray-400">{{ __('Bir marta tanlanadi (tilga bog‘liq emas). Yangi rasmlar mavjudlariga qo‘shiladi.') }}</p>
                        @error('gallery.*')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                    </div>
                </div>
            </x-admin.form.section>
        </div>
    </div>
</form>
