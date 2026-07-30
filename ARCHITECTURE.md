# Clean Laravel arxitekturasi — qo'llanma

> Bu fayl SamVet Library loyihasida amalda ishlatilgan (faqat rejalashtirilgan emas — **haqiqatan qo'llanilgan**) arxitektura va clean code qoidalarini tasvirlaydi. Boshqa Laravel loyihasida xuddi shu arxitekturani qayta qurish uchun tayyorlangan.
>
> Har bir pattern quyida **haqiqiy kod misoli** bilan ko'rsatilgan — bu SamVet Library'dan olingan, ishlaydigan, testdan o'tgan kod. Hech narsa nazariy emas.

---

## 1. Falsafa

- **Controller yupqa bo'lishi shart.** U faqat: validatsiya (FormRequest'ga topshiriladi) → Service chaqiradi → javob qaytaradi. Controller ichida DB so'rovi yoki biznes `if` shartlari YO'Q.
- **Har bir qatlam bitta vazifa uchun javobgar** (Single Responsibility, qatlam darajasida):
  - **FormRequest** — kirish ma'lumoti to'g'rimi?
  - **DTO (Data)** — ma'lumot qanday shaklda Controller'dan Service'ga o'tadi?
  - **Service** — biznes qoidalari, tranzaksiya, fayl saqlash.
  - **Repository** — faqat DB so'rovlari (Eloquent shu yerda qoladi, boshqa hech qayerda emas).
  - **Model** — ma'lumot va munosabatlar (relationships), og'ir logika emas.
- **DRY, lekin erta emas.** Bir xil shakldagi 5+ oddiy CRUD resurs (lookup/reference data) bo'lsa — umumiy bazaviy klass (`Base*Repository`, `Base*Service`) yasang. Lekin 2-3 ta har xil modul uchun sun'iy abstraksiya yasamang — takrorlanish ustunlik qiladi.
- **Amalda qo'llanilmagan narsalarni CLAUDE.md'ga yozib qo'ymang.** Quyida (bo'lim 12) aynan shu loyihada REJALASHTIRILGAN, lekin oxirigacha QURILMAGAN patternlar (Pipeline, Policy) ham ochiq aytilgan — bu halollik keyingi loyihada his qilinadigan farqni oldini oladi.

---

## 2. Umumiy oqim (bitta so'rov qanday yuradi)

```
HTTP so'rov
   │
   ▼
routes/web.php ──► Controller::method(FormRequest $request)
   │                        │
   │                        ├─ FormRequest: validatsiya (rules(), withValidator())
   │                        │
   │                        ▼
   │               DTO::fromRequest($request)   ← massiv emas, tipli obyekt
   │                        │
   │                        ▼
   │               $this->service->create($dto)
   │                        │        └─ Service: biznes qoida, DB::transaction, fayl saqlash
   │                        ▼
   │               $this->repository->create($attributes)
   │                        │        └─ Repository: FAQAT shu yerda Eloquent/Query Builder
   │                        ▼
   │                     Model::create(...)
   │                        │
   ▼                        ▼
view('pages...')      Observer (creating/created — slug, hisoblagich)
   │
   ▼
resources/views/pages/{admin|client}/...blade.php
```

---

## 3. Papka tuzilishi

```
app/
├── Data/                          ← DTO'lar (Controller → Service)
├── Enums/                         ← Sehrli string/raqam o'rniga
├── Exports/                       ← Excel eksport klasslari (Maatwebsite\Excel)
├── Http/
│   ├── Controllers/
│   │   ├── Admin/                 ← admin panel controllerlari
│   │   │   └── Lookups/           ← kichik reference-data controllerlari
│   │   ├── Site/                  ← public client-sayt controllerlari
│   │   └── Auth/
│   ├── Requests/
│   │   ├── Admin/                 ← FormRequest'lar
│   │   │   └── Lookups/
│   │   └── Site/
│   └── Resources/                 ← API Resource (JSON javob formatlash)
├── Models/
├── Observers/                     ← model hodisalari (slug yaratish va h.k.)
├── Policies/                      ← [HALI YO'Q — bo'lim 12'ga qarang]
├── Providers/
│   └── RepositoryServiceProvider.php  ← interface → implementatsiya bog'lash
├── Repositories/
│   ├── Contracts/                 ← *RepositoryInterface
│   └── Eloquent/                  ← *Repository (Eloquent shu yerda)
└── Services/
    ├── Lookups/                   ← kichik reference-data servislari
    ├── Site/                      ← public-sayt uchun maxsus servislar
    └── Admin/

database/
├── factories/
├── migrations/
└── seeders/

resources/views/
├── layouts/            admin.blade.php, guest.blade.php, client.blade.php
├── partials/
│   ├── admin/          takrorlanuvchi bo'laklar (sidebar, header)
│   └── site/
├── components/
│   ├── admin/
│   │   ├── form/        ← qayta ishlatiluvchi forma inputlari (<x-admin.form.input>)
│   │   └── lookups/      ← lookup CRUD uchun umumiy jadval/modal komponentlari
│   └── site/
└── pages/
    ├── admin/{resurs}/{index,create,edit,show}.blade.php + partials/form.blade.php
    ├── auth/
    └── site/

tests/
└── Feature/
    ├── Admin/           ← har bir modul uchun *CrudTest.php
    └── *ClientTest.php  ← public-sayt sahifalari
```

---

## 4. Qatlamlar — batafsil, haqiqiy kod bilan

### 4.1 Model

- `$fillable` **aniq** ro'yxatlanadi (`$guarded = []` ISHLATILMAYDI).
- `casts()` metod sifatida (Laravel 12 uslubi), property emas.
- Ko'p-qiymatli enum ustun kerak bo'lsa — `AsEnumCollection::of(SomeEnum::class)` (pastda misol).
- Model'da og'ir logika YO'Q — faqat `$fillable`, `casts()`, munosabatlar (relationships) va juda oddiy hisoblovchi metodlar.

```php
// app/Models/BookCopy.php (qisqartirilgan)
class BookCopy extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id', 'inventory_number', 'format', 'condition', 'status',
        'location_id', 'acquisition_act_number', 'acquisition_act_at',
    ];

    protected function casts(): array
    {
        return [
            'format' => BookFormat::class,                       // oddiy enum cast
            'condition' => AsEnumCollection::of(CopyCondition::class), // KO'P qiymatli enum (JSON array)
            'status' => CopyStatus::class,
            'acquisition_act_at' => 'date',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
```

### 4.2 Enum

Har bir "sehrli string" (holat, format, til kodi) uchun backed enum, `label()` metodi bilan (foydalanuvchiga ko'rinadigan matn shu yerda, boshqa hech qayerda takrorlanmaydi):

```php
// app/Enums/CopyCondition.php
enum CopyCondition: string
{
    case New = 'new';
    case Old = 'old';
    case Torn = 'torn';
    case Repaired = 'repaired';
    case Scribbled = 'scribbled';
    case PagesIncomplete = 'pages_incomplete';

    public function label(): string
    {
        return match ($this) {
            self::New => __('Yangi'),
            self::Old => __('Eski'),
            self::Torn => __('Yirtilgan'),
            self::Repaired => __('Ta’mirlangan'),
            self::Scribbled => __('Sizilgan'),
            self::PagesIncomplete => __('Betlari to‘liq emas'),
        };
    }
}
```

FormRequest'da tekshirish: `new Enum(CopyCondition::class)` (bitta qiymat) yoki `'condition.*' => [new Enum(...)]` (massiv, ko'p-tanlash uchun). Yangi Laravel loyihasida `Illuminate\Validation\Rule::enum(...)` ham xuddi shu vazifani bajaradi — ikkalasi ham loyihada aralash ishlatiladi, farqi yo'q.

### 4.3 DTO (`app/Data/*Data.php`)

- Controller → Service ma'lumot **massiv emas, `readonly` propertyli tipli klass** orqali o'tadi.
- Ikkita statik/instance metod: `fromRequest(Request $request): self` (HTTP so'rovdan yasash) va `toAttributes(): array` (faqat DB ustunlariga yoziladigan skalyar maydonlar — fayl kabi narsalar bu yerda YO'Q, ular Service'da alohida ishlanadi).

```php
// app/Data/CopyData.php
class CopyData
{
    public function __construct(
        public readonly string $inventory_number,
        public readonly string $format,
        /** @var array<int, string> */
        public readonly array $condition,
        public readonly string $status,
        public readonly ?int $location_id,
        public readonly ?string $acquisition_act_number,
        public readonly ?string $acquisition_act_at,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            inventory_number: $request->string('inventory_number')->toString(),
            format: $request->string('format')->toString(),
            condition: $request->input('condition', []),
            status: $request->string('status')->toString(),
            location_id: $request->integer('location_id') ?: null,
            acquisition_act_number: $request->input('acquisition_act_number') ?: null,
            acquisition_act_at: $request->input('acquisition_act_at') ?: null,
        );
    }

    /** @return array<string, mixed> */
    public function toAttributes(): array
    {
        return [
            'inventory_number' => $this->inventory_number,
            'format' => $this->format,
            'condition' => $this->condition,
            'status' => $this->status,
            'location_id' => $this->location_id,
            'acquisition_act_number' => $this->acquisition_act_number,
            'acquisition_act_at' => $this->acquisition_act_at,
        ];
    }
}
```

**Qoida:** yangi ustun qo'shilganda — 4 joyni birga o'zgartirish kerak: constructor property, `fromRequest()`, `toAttributes()`, va FormRequest'dagi validatsiya qoidasi. Bularning biri unutilsa — ma'lumot jimgina yo'qoladi (validatsiyadan o'tadi, lekin DB'ga yozilmaydi). Shuning uchun DTO uchun har doim kamida bitta "create + saqlaydi" testi yozing.

### 4.4 FormRequest

- Har bir forma/so'rov uchun alohida. Update odatda Store'ni **extends** qiladi (qoidalar bir xil bo'lsa qayta yozilmaydi):

```php
// app/Http/Requests/Admin/UpdateCopyRequest.php — TO'LIQ fayl
class UpdateCopyRequest extends StoreCopyRequest
{
    protected function inventoryNumberUniqueRule(): object
    {
        return Rule::unique('book_copies', 'inventory_number')->ignore($this->route('copy'));
    }
}
```

- Bir necha maydon bir-biriga bog'liq bo'lsa (masalan, "daraja PhD bo'lsa — fan nomi majburiy") — `withValidator()`:

```php
public function withValidator(Validator $validator): void
{
    $validator->after(function (Validator $validator) {
        $degree = $this->input('degree');

        if (in_array($degree, ['phd', 'dsc'], true)) {
            if (! $this->filled('science_field_id')) {
                $validator->errors()->add('science_field_id', __('Fan nomini tanlang.'));
            }
        } elseif ($degree === 'master' && ! $this->filled('master_specialty_id')) {
            $validator->errors()->add('master_specialty_id', __('Mutaxassislikni tanlang.'));
        }
    });
}
```

- `attributes()` metodi — validatsiya xato xabarlarida maydon nomi o'rniga inson o'qiy oladigan label chiqishi uchun (`:field` → "Resurs sohasi", "field" emas).

### 4.5 Repository

**Interface** (`app/Repositories/Contracts/`) — shartnoma, hech qanday Eloquent yo'q:

```php
interface CopyRepositoryInterface
{
    public function create(array $data): BookCopy;
    public function update(BookCopy $copy, array $data): BookCopy;
    public function delete(BookCopy $copy): void;
}
```

**Eloquent implementatsiya** (`app/Repositories/Eloquent/`) — **faqat shu yerda** DB so'rovi:

```php
class BookRepository implements BookRepositoryInterface
{
    public function filtered(array $filters = []): Builder
    {
        return Book::query()
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->when($filters['category_id'] ?? null, function ($query, int $categoryId) {
                $query->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId));
            });
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->filtered($filters)->latest('id')->paginate($perPage)->withQueryString();
    }
}
```

> **Muhim, halol eslatma:** CLAUDE.md'da "murakkab filtr/qidiruv → Pipeline pattern (uzun `if` zanjiri yo'q)" deb yozilgan bo'lsa-da, **bu loyihada Pipeline hech qachon qurilmagan**. Barcha filtr/qidiruv Repository ichida `->when()` zanjiri orqali qilingan — va bu amalda YETARLI bo'lib chiqdi, chunki `when()` shartlar sonini ko'paytirmaydi, faqat query builder'ga shartli qo'shimcha qiladi. Yangi loyihada Pipeline pattern faqat filtr shartlari **haqiqatan** 6-7 tadan oshib, `when()` zanjiri o'qib bo'lmas darajaga yetsagina kiriting — oldindan emas.

### 4.6 Service

Biznes logika, tranzaksiya, fayl saqlash — shu yerda:

```php
class LoanService
{
    public function __construct(
        private readonly LoanRepositoryInterface $loans,
    ) {}

    public function returnLoan(Loan $loan, array $returnedConditions = []): Loan
    {
        if ($loan->status !== LoanStatus::OnLoan) {
            return $loan; // allaqachon qaytarilgan — hech narsa qilinmaydi
        }

        $loan = DB::transaction(function () use ($loan, $returnedConditions) {
            $this->loans->update($loan, [
                'returned_at' => now(),
                'status' => LoanStatus::Returned,
                'returned_condition' => $returnedConditions,
            ]);

            $copyUpdates = ['status' => CopyStatus::Available];
            if ($returnedConditions !== []) {
                $copyUpdates['condition'] = $returnedConditions;
            }
            $loan->loanable?->update($copyUpdates);

            return $loan;
        });

        $this->forgetOverdueCache();

        return $loan;
    }
}
```

**Qoidalar:**
- Bir nechta model o'zgaradigan har qanday amal → `DB::transaction(...)` ichida (yuqoridagi misolda: Loan HAM, BookCopy HAM birga yangilanadi — biri muvaffaqiyatsiz bo'lsa ikkalasi ham qaytariladi).
- Fayl yuklash/o'chirish Service'da (Controller'da EMAS):

```php
private function storeProtected(UploadedFile $file): string
{
    return $file->store(self::ELECTRONIC_DIR, 'local'); // public EMAS — controller orqali stream qilinadi
}
```

### 4.7 Controller

Yupqa. Validatsiya yo'q, DB so'rovi yo'q — faqat chaqiruv zanjiri:

```php
class LoanController extends Controller
{
    public function __construct(private readonly LoanService $loanService) {}

    public function return(ReturnLoanRequest $request, Loan $loan): RedirectResponse
    {
        $conditions = collect($request->input('returned_condition', []))
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => CopyCondition::from($value))
            ->values()
            ->all();

        $this->loanService->returnLoan($loan, $conditions);

        return redirect()
            ->route('admin.readers.show', $loan->reader)
            ->with('success', __('Material qaytarildi.'));
    }
}
```

Xatoni oldindan bilib bo'lmaydigan biznes holatlar (masalan "o'quvchi bloklangan") — Service `RuntimeException` otadi, Controller uni tutadi va formaga qaytaradi:

```php
public function store(StoreLoanRequest $request, Reader $reader): RedirectResponse
{
    try {
        $this->loanService->issueByInventory($reader, $request->string('inventory_number')->toString(), ...);
    } catch (RuntimeException $e) {
        return redirect()->route('admin.readers.show', $reader)->withInput()
            ->withErrors(['inventory_number' => $e->getMessage()]);
    }

    return redirect()->route('admin.readers.show', $reader)->with('success', __('Material berildi.'));
}
```

### 4.8 Observer

Model hodisasiga bog'liq logika (slug yaratish, hisoblagich) — Model'ni shishirtirmaslik uchun Observer'da:

```php
// app/Observers/BookObserver.php — TO'LIQ fayl
class BookObserver
{
    public function creating(Book $book): void
    {
        if (empty($book->slug)) {
            $book->slug = $this->uniqueSlug($book->title);
        }
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;

        while (Book::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
```

Model'da ulash — PHP 8 attribute orqali (`protected static function boot()` EMAS, bu eskirgan uslub):

```php
#[ObservedBy([BookObserver::class])]
class Book extends Model { ... }
```

### 4.9 API Resource (JSON javoblar)

Live-search yoki boshqa JSON endpoint javobi — har doim Resource orqali formatlanadi, Controller'da qo'lda massiv yig'ilmaydi:

```php
// app/Http/Resources/JournalSearchResource.php — TO'LIQ fayl
/** @mixin Journal */
class JournalSearchResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type?->name,
            'has_issues' => $this->issues_count > 0,
        ];
    }
}
```

### 4.10 Aqilli qidiruv (Smart search) — server infratuzilmasisiz

Boshida bu loyihada "aqilli qidiruv = Laravel Scout + Meilisearch" deb rejalashtirilgan edi (bo'lim 12'dagi kabi — reja va amaliyot farq qilishi mumkin). Amalda production serverda root/sudo huquqi bo'lmagani sababli (ko'p shared-hosting shunday) Meilisearch kabi alohida xizmatni o'rnatib bo'lmadi. Buning o'rniga **faqat MySQL FULLTEXT + Repository qatlamidagi PHP mantiq** bilan real relevantlik va imlo-xato tolerantligiga erishildi — hech qanday yangi server jarayoni, port yoki papka kerak emas, oddiy migratsiya + kod bilan ishlaydi.

**Nega bu muhim namuna:** qidiruv mantig'i butunlay Repository qatlamida izolyatsiya qilingan (Controller/Service/Blade uni bilmaydi — faqat filtr obyektini uzatadi). Shu sababli kelajakda server imkoniyati kengaysa, Meilisearch'ga o'tish **faqat shu bitta qatlamni almashtirish** bilan cheklanadi — bu Clean Architecture'ning aynan shu loyihada isbotlangan foydasi.

**Qatlamlar:**

1. **Migratsiya** — composite FULLTEXT indeks (bitta `MATCH()` chaqiruvi ustunlar ro'yxati bilan aniq mos kelishi shart):
   ```php
   Schema::table('books', function (Blueprint $table) {
       $table->fullText(['title', 'authors', 'annotation', 'udc']);
   });
   ```

2. **Repository — `applySmartSearch()`** — uch qatlamli tolerantlik:
   - So'rov so'zlarga bo'linadi, stop-so'zlar (kichik statik uz/ru ro'yxat) olib tashlanadi.
   - **3 belgidan uzun so'zlar** → `MATCH(...) AGAINST('so'z*' IN BOOLEAN MODE)` — trailing-wildcard prefiks qisqartirish/typo'ga chidamli qiladi.
   - **3 belgidan qisqa so'zlar** (`innodb_ft_min_token_size` server config talab qiladi — root kerak, yo'q bo'lsa qisqa so'z FULLTEXT'da umuman indekslanmaydi) → so'z-chegara taxminlangan `LIKE` (`"so'z %"` / `"% so'z %"` / `"% so'z"` / `= so'z`) — bare `%so'z%` emas, chunki 2-3 harfli substring deyarli har narsaga mos keladi.
   - **Sarlavha boost** — `(CASE WHEN title LIKE ? THEN 10 ELSE 0 END)` relevantlikka qo'shiladi: FULLTEXT'ning o'z TF-IDF balli kichik/mavzu-siljigan korpusda (masalan bitta soha nomi ko'p yozuvda takrorlansa) ishonchli emas — sarlavha mosligi doim ustunlik qilishi kerak bo'lgan aniq signal.
   - **NATURAL LANGUAGE MODE ishlatilmaydi** — ikkita sabab: (a) yuqoridagi korpus-siljish muammosi, (b) **muhim, kutilmagan cheklov**: MySQL/InnoDB FULLTEXT `MATCH()` tranzaksiya ichida hali commit qilinmagan qatorlarni ko'rmasligi mumkin — bu avtomatik testlarda (`RefreshDatabase` har testni tranzaksiyaga o'rab, oxirida rollback qiladi) FULLTEXT natijasi doim bo'sh chiqishiga olib keladi. Pastga qarang.

3. **Stopword-only so'rov himoyasi** — agar so'rov faqat stop-so'zlardan iborat bo'lsa, `whereRaw('1 = 0')` bilan aniq bo'sh natija qaytariladi (aks holda `->when()` shartsiz ishga tushib, BUTUN jadvalni qaytarib yuboradi — nozik, lekin real xato).

4. **Typo-tolerant fallback — alohida Service** (`SearchSuggestionService`), faqat 0 natija chiqqanda chaqiriladi:
   - Kichik hajmdagi (yuz-mingga yaqin) DB'dan `DISTINCT` so'zlar korpusi olinadi.
   - Har bir so'rov so'ziga eng yaqin korpus so'zi **multi-byte-safe** Levenshtein masofasi bilan topiladi (PHP'ning tayyor `levenshtein()` funksiyasi BAYT asosida ishlaydi, 255 bayt chegarasi bor — kirill/ko'p-baytli alifbolar uchun noto'g'ri natija beradi; `mb_str_split()` bilan o'z Wagner–Fischer implementatsiyasi yozilgan).
   - Masofa chegaradan (masalan ≤2) katta bo'lsa — taklif yo'q (`null`).
   - **Faqat havola emas — natijalar avtomatik ko'rsatiladi.** To'g'irlangan so'z topilsa, Service o'sha so'z bilan qidiruvni **qayta ishga tushiradi** (`CatalogFilters::withSearch()` — bir maydoni almashtirilgan yangi immutable nusxa) va haqiqiy natijalarni banner bilan qaytaradi ("«X» uchun natija topilmadi, «Y» bo'yicha ko'rsatilmoqda"). Foydalanuvchi taklifni bosishi shart emas. To'g'irlangan so'z ham (boshqa faol filtrlar bilan birga) 0 natija bersa, tuzatish "da'vo qilinmaydi" — oddiy bo'sh natija holati qaytadi. Xuddi shu tolerantlik `quickSearch()`'da ham qo'llanadi (to'g'ridan-to'g'ri moslik 0 bo'lsa, to'g'irlangan so'z bilan qayta so'raladi) — shuning uchun tezkor (typeahead) qidiruv ham imlo xatosiga chidamli.

5. **Tezkor (typeahead) endpoint** — mavjud ikki-bosqichli (skinny-rows → hydrate) paginate arxitekturasidan alohida, yengil `quickSearch(string $term, int $limit)` metodi — bir nechta turdagi resursni birlashtirib, top-N ni Resource orqali qaytaradi. Frontend — debounce qilingan Alpine `fetch()` (bo'lim 4.9'dagi Resource naqshi bilan bir xil).

> **Test infratuzilmasi bo'yicha muhim eslatma:** yuqorida aytilgan FULLTEXT+tranzaksiya cheklovi sababli, FULLTEXT'ga bevosita bog'liq testlar (`MATCH()` natijasini tekshiradigan) alohida faylda, `RefreshDatabase` o'rniga `DatabaseTruncation` bilan yozilishi kerak (haqiqatan commit+truncate, tranzaksiya-rollback emas) — bo'lim 9'ga qarang. Bu sekinroq (~10s/test, millisekund o'rniga), shuning uchun **faqat FULLTEXT'ga chinakam bog'liq testlarni** shu faylga qo'ying — qolganini (stopword, LIKE-fallback, did-you-mean, Resource shakli) tezroq standart `RefreshDatabase`'da qoldiring.

---

## 5. DRY: bir xil shakldagi ko'p resurs uchun bazaviy klasslar

Loyihada ~25 ta "lookup" (kategoriya, til, joylashuv, kafedra va h.k.) bor — barchasi bir xil CRUD shaklida. Har biriga alohida to'liq Repository/Service yozish o'rniga — **umumiy bazaviy klass + kichik konkret klass**:

```
LookupRepositoryInterface (umumiy shartnoma)
        ▲
        │ implements
BaseLookupRepository (abstract — all()/find()/create()/update()/delete())
        ▲
        │ extends, faqat model() ni belgilaydi
DepartmentCoverageRepository (10 qatordan kam)
```

```php
// app/Repositories/Eloquent/DepartmentCoverageRepository.php — TO'LIQ fayl
class DepartmentCoverageRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return DepartmentCoverage::class;
    }

    /** Stable insertion order — boshqa lookup'lardek "eng yangisi birinchi" emas. */
    protected function scopeIndex($query)
    {
        return $query->orderBy('id');
    }
}
```

Xuddi shunday `BaseLookupService` + `DepartmentCoverageService extends BaseLookupService` (faqat `attributes()` metodini override qiladi, agar qo'shimcha maydon — masalan `percentage` — kerak bo'lsa).

**Qachon bazaviy klass yasash kerak:** kamida 3-4 ta modul aynan bir xil shaklda takrorlansa. Undan kam bo'lsa — takrorlanishga yo'l qo'ying, erta abstraksiya keyinchalik qimmatga tushadi.

**Muhim:** bazaviy klassdan foydalanadigan kichik resurslar uchun `RepositoryServiceProvider`'da alohida interface bog'lash **shart emas** — Controller/Service konkret klassni to'g'ridan-to'g'ri type-hint qiladi (`DepartmentCoverageRepository`, interface emas). Faqat "katta" resurslar (Book, Article, Journal...) — bir nechta joyda mock qilinishi yoki almashtirilishi mumkin bo'lganlar — interface orqali bog'lanadi.

---

## 6. Bog'lash: `RepositoryServiceProvider`

Laravel'ning o'rnatilgan `$bindings` massividan foydalaniladi (`register()` metodini qo'lda yozish shart emas):

```php
class RepositoryServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        BookRepositoryInterface::class => BookRepository::class,
        LoanRepositoryInterface::class => LoanRepository::class,
        // ... yangi "katta" resurs qo'shilganda shu yerga bitta qator qo'shiladi
    ];
}
```

---

## 7. Blade view tuzilishi

```
layouts/admin.blade.php          ← @extends shu yerdan
partials/admin/sidebar.blade.php  ← takrorlanuvchi bo'lak
components/admin/form/input.blade.php  ← <x-admin.form.input name="..." :label="..." />
pages/admin/books/index.blade.php ← @extends('layouts.admin')
pages/admin/books/partials/form.blade.php  ← create+edit ikkalasida ham @include qilinadi
```

- Controller view'ni **to'liq yo'l** bilan chaqiradi: `view('pages.admin.books.index')`.
- Bitta forma (create+edit) uchun bitta `partials/form.blade.php` — ikki marta yozilmaydi, `$book ?? null` orqali farqlanadi.
- Ko'p-tanlash maydoni (multi-select) uchun umumiy Alpine.js komponenti: `<x-admin.form.multiselect name="condition" :options="..." :selected="..." />` — har bir formada qayta yozilmaydi.
- **Bir xil UI ikkita sahifada ishlatilsa (masalan "materialni qaytarish" oynasi ham o'quvchi sahifasida, ham "berilgan kitoblar" sahifasida) — umumiy Blade komponentiga chiqariladi**, aks holda ikkalasi asta-sekin bir-biridan farqlanib, birida tuzatilgan xato ikkinchisida qolib ketadi (bu loyihada aynan shunday xato bo'lgan va keyin komponentga chiqarilgan).

---

## 8. Xavfsizlik (majburiy tekshiruv ro'yxati)

- **Mass-assignment:** `$fillable` aniq, `$guarded = []` YO'Q.
- **Avtorizatsiya:** har bir admin route `auth` middleware ostida. Agar rollar kerak bo'lsa — Policy (bu loyihada hali yo'q, lekin FormRequest'larning `authorize()` metodida shunday izoh qoldirilgan: `// Route is under 'auth' middleware. If roles are added — Policy.` — bu ataylab qoldirilgan "TODO", keyingi loyihada kerak bo'lganda qo'shiladi).
- **SQL injection:** faqat Eloquent/Query Builder. Xom SQL string konkatenatsiya YO'Q. Ustun nomi dinamik bo'lganda ham (masalan, migratsiyada `$column.'_at'`) — bu ishonchli, o'zingiz yozgan sobit ro'yxatdan kelgan bo'lishi kerak, foydalanuvchi kiritgan qiymatdan EMAS.
- **Fayl yuklash:** mime-type/hajm FormRequest'da tekshiriladi (`'mimes:pdf', 'max:972800'`). Himoyalangan fayllar `storage/app` (public EMAS), controller orqali stream qilinadi, hech qachon to'g'ridan-to'g'ri URL bilan berilmaydi.
- **CSRF:** barcha formalarda `@csrf`.

---

## 9. Testlash konvensiyasi (Pest)

Har bir modul uchun `tests/Feature/Admin/{Resurs}CrudTest.php`:

```php
beforeEach(fn () => actingAsAdmin());

it('creates a X with Y field', function () {
    $this->post(route('admin.x.store'), [...])->assertRedirect();

    $model = X::firstWhere('title', '...');
    expect($model->y)->toBe('...');
});

it('does not show a Z field anywhere — regression guard', function () {
    $this->get(route('admin.x.create'))->assertDontSee(__('Z label'));
    $this->get(route('admin.x.index'))->assertDontSee(__('Z label'));
});
```

**Muhim qoidalar (haqiqiy xatolardan kelib chiqqan tajriba):**
- **Regression testi yozing, faqat happy-path emas.** Bir maydon "olib tashlanishi kerak" deyilganda — shu maydon HAQIQATAN yo'qligini tasdiqlovchi test yozing (`assertDontSee`), shunchaki "olib tashladim" deb aytib qo'ymang. Bu loyihada bir maydon 3 marta "olib tashlandi" deyilib, aslida faqat o'xshash boshqa modulda olib tashlangan, asl modulda qolib ketgan — test bo'lganida bu darhol ko'rinar edi.
- **Fayl-level `beforeEach(fn () => actingAsAdmin())` "guest bloklanishi" testini buzadi** — chunki u ham autentifikatsiya qilib qo'yadi. Yechim: o'sha bitta testning boshida `auth()->logout();`.
- **Migratsiyani ustun turini o'zgartirsangiz** (masalan string → JSON array), haqiqiy lokal DB'ga qarshi `php artisan migrate --step` orqali tekshiring — `migrate:fresh` emas, chunki bu haqiqiy ma'lumotni o'chirib yuboradi.
- **MySQL FULLTEXT (`MATCH()...AGAINST()`) testi bo'lsa — `RefreshDatabase` yetarli EMAS.** `RefreshDatabase` har testni tranzaksiyaga o'rab, oxirida rollback qiladi; InnoDB FULLTEXT indeksi hali commit qilinmagan qatorni ko'rmasligi mumkin — test tasodifiy/doim muvaffaqiyatsiz bo'lib chiqadi, lekin xato o'zingizning so'rov mantig'ingizda emas. Yechim: shu testlarni alohida faylga chiqarib, `tests/Pest.php`'da o'sha faylga maxsus `pest()->use(DatabaseTruncation::class)->in('Feature/YourFulltextTest.php');` qo'shing (haqiqiy commit+truncate, tranzaksiya emas) — bu sekinroq (~10x), shuning uchun faqat FULLTEXT'ga chinakam bog'liq testlarni shu faylga qo'ying, qolganini standart `RefreshDatabase` tezligida qoldiring (bo'lim 4.10'ga qarang).
- Har bir backend o'zgarishdan keyin: `php -l` (sintaksis) → tegishli test fayli → **to'liq test to'plami** (`php artisan test --exclude-group=browser`) — birortasi ham o'tkazib yuborilmasin.

---

## 10. Migratsiya konvensiyalari

**Yangi ustun qo'shish** — oddiy, standart.

**Ustun turini o'zgartirish (masalan bitta qiymatdan JSON array'ga)** — ma'lumotni yo'qotmasdan, chunked qayta yozish orqali:

```php
public function up(): void
{
    Schema::table('loans', function (Blueprint $table) {
        $table->text('issued_condition_new')->nullable()->after('issued_condition');
    });

    // DB::table (Eloquent EMAS) — eski string-cast bilan aralashmasligi uchun
    DB::table('loans')->whereNotNull('issued_condition')->orderBy('id')->chunkById(200, function ($rows) {
        foreach ($rows as $row) {
            DB::table('loans')->where('id', $row->id)->update([
                'issued_condition_new' => json_encode([$row->issued_condition]),
            ]);
        }
    });

    Schema::table('loans', fn (Blueprint $table) => $table->dropColumn('issued_condition'));
    Schema::table('loans', fn (Blueprint $table) => $table->renameColumn('issued_condition_new', 'issued_condition'));
}
```

`down()` — teskari yo'nalishda xuddi shunday (JSON array'dan birinchi elementni olib, eski ustunga qaytarish).

**Har bir migratsiya docblock'da NEGA ekanligini yozadi** (nima qilayotgani kod o'zidan ko'rinib turibdi, lekin sabab ko'rinmaydi):

```php
/**
 * "Holati" (condition) becomes multi-select — a copy/record can carry
 * several condition tags at once, not just one. Converts the plain
 * string column to a JSON array.
 */
```

---

## 11. Kod izohlari va i18n

- **Kod izohlari (comments) — INGLIZCHA**, hatto suhbat/loyiha tili boshqa bo'lsa ham (bu loyihada o'zbekcha). Bu kelajakda xalqaro jamoada ishlash yoki ochiq-manba qilish imkonini yo'qotmaslik uchun.
- **Foydalanuvchiga ko'rinadigan matn** — `{{ __('...') }}` orqali, loyiha tilida (bu yerda o'zbekcha). Boshidanoq shunday yoziladi, keyin "tarjima qilib qo'yamiz" deb qoldirilmaydi.
- Enum `label()` metodlari — foydalanuvchi matni shu YERDA markazlashadi, boshqa hech qayerda qattiq yozilgan (hardcoded) string bo'lmasligi kerak.

---

## 12. Bu loyihada REJALASHTIRILGAN, lekin QURILMAGAN patternlar

Halollik uchun — CLAUDE.md'da yozilgan, lekin amalda hech qachon ishlatilmagan narsalar:

| Pattern | Holat | Nega |
|---|---|---|
| **Pipeline** (murakkab filtr uchun) | Qurilmagan | `->when()` zanjiri yetarli bo'lib chiqdi, filtr shartlari hech qachon Pipeline talab qiladigan darajada murakkablashmadi |
| **Policy/Gate** | Qurilmagan | Loyihada faqat bitta admin roli bor edi (rol tizimi yo'q) — `auth` middleware yetarli edi |
| **CQRS, Event Sourcing** | Rejalashtirilmagan ham | Loyiha ko'lami buni talab qilmadi |

**Xulosa:** yangi loyihada bu patternlarni CLAUDE.md'ga "standart" deb yozmang — kerak bo'lib qolganda (masalan, ro'l tizimi qo'shilganda Policy, yoki filtr 6-7 shartdan oshganda Pipeline) o'shanda qo'shing va CLAUDE.md'ni **o'sha paytda** yangilang. Oldindan yozilgan, amalda tekshirilmagan qoida — chalg'ituvchi hujjat bo'lib qoladi.

---

## 13. Checklist: yangi modul qo'shish

"Katta" resurs (Book, Article kabi — o'z sahifalari, filtri, eksporti bor):

1. Migration (jadval)
2. `Model` + `$fillable` + `casts()`
3. `Factory` (test uchun)
4. `Enum`'lar (agar sehrli string bo'lsa)
5. `Data/{X}Data.php` — DTO
6. `Repositories/Contracts/{X}RepositoryInterface.php`
7. `Repositories/Eloquent/{X}Repository.php`
8. `RepositoryServiceProvider`'ga bog'lash qo'shish
9. `Services/{X}Service.php`
10. `Http/Requests/Admin/Store{X}Request.php` (+ `Update{X}Request extends Store{X}Request`)
11. `Http/Controllers/Admin/{X}Controller.php`
12. `routes/web.php`'ga `Route::resource(...)`
13. Blade: `pages/admin/{x}/{index,create,edit,show}.blade.php` + `partials/form.blade.php`
14. `Observer` (agar slug/hisoblagich kerak bo'lsa) + Model'da `#[ObservedBy([...])]`
15. `tests/Feature/Admin/{X}CrudTest.php` — create/update/delete/validatsiya + kamida bitta regression testi
16. `php -l` → tegishli testlar → to'liq test to'plami — barchasi yashil bo'lgandan keyingina "tayyor" deb hisoblang

Kichik "lookup" resurs (reference data, 2-3 maydon):

1. Migration + Model (`HasTranslations` agar ko'p tilli bo'lsa)
2. `Repositories/Eloquent/{X}Repository.php extends BaseLookupRepository` (faqat `model()`)
3. `Services/Lookups/{X}Service.php extends BaseLookupService` (faqat qo'shimcha maydon bo'lsa `attributes()` override)
4. `Http/Requests/Admin/Lookups/{X}Request.php`
5. `Http/Controllers/Admin/Lookups/{X}Controller.php`
6. Umumiy Blade komponentlari (`components/admin/lookups/*`) — yangi UI yozilmaydi, mavjud komponentga prop qo'shiladi
7. Test + tekshiruv
