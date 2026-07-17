@php
    $book = $book ?? null;
    $editing = ! is_null($book);
    $sourceBook = $sourceBook ?? null;
    // Translation-creation mode: new book, but shared fields are copied from the source
    $translating = ! $editing && ! is_null($sourceBook);

    // Prefill values for shared fields (old() takes priority, then source)
    $preAuthorIds = $translating ? old('author_ids', $sourceBook->authors->pluck('id')->all()) : ($editing ? $book->authors->pluck('id')->all() : []);
    $preCategoryIds = $translating ? old('category_ids', $sourceBook->categories->pluck('id')->all()) : ($editing ? $book->categories->pluck('id')->all() : []);
    $preBookTypeId = $translating ? old('book_type_id', $sourceBook->book_type_id) : $book?->book_type_id;
    $prePublicationPlaceId = $translating ? old('publication_place_id', $sourceBook->publication_place_id) : $book?->publication_place_id;
    $prePublisher = $translating
        ? old('publisher', $sourceBook->getTranslations('publisher'))
        : ($editing ? $book->getTranslations('publisher') : []);
    $prePublicationYear = $translating ? old('publication_year', $sourceBook->publication_year) : $book?->publication_year;

    $authorOptions = $authors->map(fn ($a) => ['id' => $a->id, 'label' => $a->name])->all();
    $categoryOptions = $categories->map(fn ($c) => [
        'id' => $c->id,
        'label' => $c->parent ? $c->parent->name . ' › ' . $c->name : $c->name,
    ])->all();
@endphp

<form
    method="POST"
    action="{{ $editing ? route('admin.books.update', $book) : route('admin.books.store') }}"
    enctype="multipart/form-data"
    x-data="uploadForm"
    @submit="submitUpload($event)"
>
    @csrf
    @if ($editing) @method('PUT') @endif

    <x-admin.form.upload-errors />
    <x-admin.form.uploading-overlay />
    @if ($translating)
        <input type="hidden" name="translation_of" value="{{ $sourceBook->id }}">
    @endif

    {{-- Translation mode banner --}}
    @if ($translating)
        <x-alert type="info" class="mb-6">
            {{ __('«:title» asariga boshqa tildagi nashr qo‘shyapsiz. Umumiy ma’lumotlar ko‘chirildi — til, sarlavha va boshqa maydonlarni to‘ldiring.', ['title' => $sourceBook->title]) }}
        </x-alert>
    @endif

    {{-- Header + actions (sticky) --}}
    <div class="sticky top-16 z-9 -mx-4 mb-6 flex items-center justify-between border-b border-gray-200 bg-gray-50/90 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 dark:border-gray-800 dark:bg-gray-900/90">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.books.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800">&larr;</a>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">
                {{ $editing ? __('Kitobni tahrirlash') : ($translating ? __('Yangi tarjima nashri') : __('Yangi kitob')) }}
            </h2>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.books.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400">{{ __('Bekor qilish') }}</a>
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
                    <x-admin.form.input name="title" :label="__('Sarlavha')" :value="$book?->title" required :placeholder="__('Kitob nomi')" />

                    <x-admin.form.multiselect name="author_ids" :label="__('Mualliflar')" :options="$authorOptions"
                        :selected="$preAuthorIds" :placeholder="__('Muallif(lar)ni tanlang')"
                        creatable create-type="author" :create-label="__('Yangi muallif...')" />

                    <x-admin.form.contributors-input :roles="$contributorRoles" :label="__('Boshqa ishtirokchilar')"
                        :value="$book?->contributors->map(fn ($c) => ['contributor_role_id' => $c->contributor_role_id, 'name' => $c->name])"
                        :help="__('Muallif yo‘q yoki bo‘lsa ham — muharrir, tarjimon kabi boshqa ishtirokchilarni shu yerda qo‘shing.')" />

                    @php
                        // The language is picked first: it decides which translation
                        // the book type options are labelled with.
                        $languageLocales = collect($languages)->mapWithKeys(fn ($l) => [(string) $l->id => $l->locale]);
                        $typeTranslations = collect($types)->mapWithKeys(fn ($t) => [(string) $t->id => $t->getTranslations('name')]);
                    @endphp

                    <div class="grid gap-5 sm:grid-cols-3">
                        <x-admin.form.select name="language_id" :label="__('Tili')" :options="$languages" :selected="$book?->language_id" :placeholder="__('Tanlang')"
                            :locale-map="$languageLocales"
                            creatable create-translatable create-type="language" :create-label="__('Yangi til')" />
                        <x-admin.form.select name="book_type_id" :label="__('Turi')" :options="$types" :selected="$preBookTypeId" :placeholder="__('Tanlang')"
                            :translations="$typeTranslations" await-locale
                            creatable create-translatable create-type="book_type" :create-label="__('Yangi tur')" />
                        <x-admin.form.select name="publication_place_id" :label="__('Nashriyot joyi')" :options="$publicationPlaces" :selected="$prePublicationPlaceId" :placeholder="__('Tanlang')"
                            creatable create-translatable create-type="publication_place" :create-label="__('Yangi nashriyot joyi')" />
                    </div>
                </div>
            </x-admin.form.section>

            <x-admin.form.section :title="__('Qo‘shimcha ma’lumotlar')">
                <div class="space-y-5">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-admin.form.input name="udc" :label="__('UO‘K')" :value="$book?->udc" :placeholder="__('masalan: 330.1')" />
                        <x-admin.form.input name="author_mark" :label="__('Avtorlik belgi')" :value="$book?->author_mark" :placeholder="__('masalan: O-56')" />
                    </div>
                    <div class="grid gap-5 sm:grid-cols-3">
                        <x-admin.form.input name="isbn" :label="__('ISBN')" :value="$book?->isbn" />
                        <x-admin.form.input name="publication_year" type="number" :label="__('Nashr yili')" :value="$prePublicationYear" placeholder="2024" />
                        <x-admin.form.input name="pages" type="number" :label="__('Sahifalar soni')" :value="$book?->pages" />
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-admin.form.input name="print_run" type="number" :label="__('Tiraj')" :value="$book?->print_run" />
                    </div>

                    <x-admin.form.translatable-input name="publisher" :label="__('Nashriyoti')"
                        :value="$prePublisher"
                        :placeholders="['uz' => 'masalan: Iqtisod-moliya', 'ru' => 'например: Иктисод-молия', 'kk' => 'mısalı: Iqtisod-moliya']" />
                </div>
            </x-admin.form.section>

            <x-admin.form.section :title="__('Annotatsiya')">
                <x-admin.form.textarea name="annotation" :value="$book?->annotation" :rows="5" :placeholder="__('Kitob haqida qisqacha...')" />
            </x-admin.form.section>

            <x-admin.form.section :title="__('Kategoriyalar')" :description="__('Kitob tegishli bo‘lgan mavzu(lar)')">
                <x-admin.form.multiselect name="category_ids" :options="$categoryOptions"
                    :selected="$preCategoryIds" :placeholder="__('Kategoriya(lar)ni tanlang')"
                    creatable create-translatable create-with-parent :create-parents="$categoryOptions"
                    create-type="category" :create-label="__('Yangi kategoriya')" />
            </x-admin.form.section>
        </div>

        {{-- RIGHT: cover + files --}}
        <div class="col-span-12 space-y-6 xl:col-span-4">
            <x-admin.form.section :title="__('Muqova rasmi')">
                <x-admin.form.file name="cover" :image="true" accept="image/*"
                    :currentUrl="$book?->cover_image ? asset('storage/' . $book->cover_image) : null"
                    :help="__('JPG/PNG, 2 MB gacha')" />
            </x-admin.form.section>

            <x-admin.form.section :title="__('Raqamli fayllar')" :description="__('Onlayn o‘qish/tinglash (login bilan)')">
                <div class="space-y-5">
                    <x-admin.form.file name="electronic_file" :label="__('Elektron kitob (PDF)')" accept="application/pdf" with-progress
                        :currentName="$book?->electronic_file ? basename($book->electronic_file) : null" :help="__('PDF, 950 MB gacha')" />
                    <x-admin.form.file name="audio_file" :label="__('Audio (mp3)')" accept="audio/*"
                        :currentName="$book?->audio_file ? basename($book->audio_file) : null" :help="__('MP3, 100 MB gacha')" />
                </div>
            </x-admin.form.section>
        </div>
    </div>

</form>
