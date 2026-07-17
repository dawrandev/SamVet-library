# SamVet Library — Loyiha qoidalari (CLAUDE.md)

> Bu fayl HAR BIR Claude seansida o'qiladi. Loyihada kod yozayotgan har qanday
> chat quyidagi arxitektura va clean code qoidalariga **majburiy** amal qilishi shart.

## Loyiha haqida

Samarqand veterinariya universiteti **elektron kutubxonasi**. Bitta Laravel 12 monolit
ichida **admin panel** + **ochiq client sayt**. Kitoblar PDF formatida, onlayn o'qiladi.

## Texnologiya (qat'iy)

- **Laravel 12** monolit (alohida API/SPA EMAS).
- **Blade + Tailwind CSS 4 + Alpine.js** (Vite orqali bundle, CDN EMAS).
- **Livewire ISHLATILMAYDI** (tezlik talabi). Interaktivlik = Alpine.js + yengil JSON endpointlar.
- **Filament ISHLATILMAYDI** (sekin). Admin UI = TailAdmin uslubidagi qo'lda Blade.
- DB: MySQL. Qidiruv: full-text (boshda MySQL FULLTEXT, keyin Scout+Meilisearch bo'lishi mumkin).

## Arxitektura: Controller → Service → Repository

Hech qachon biznes logika yoki DB so'rovini Controller'ga yozma. Qatlamlar:

```
app/
├── Http/
│   ├── Controllers/{Admin,Client,Auth}/   ← YUPQA: so'rov oladi, Service chaqiradi, javob qaytaradi
│   └── Requests/{Admin,Client}/           ← validatsiya (FormRequest)
├── Services/                              ← biznes logika, tranzaksiya, repolarni boshqarish
├── Repositories/
│   ├── Contracts/   *RepositoryInterface  ← interfeys (shartnoma)
│   └── Eloquent/    *Repository           ← FAQAT shu yerda Eloquent/DB query
├── Models/
└── Providers/RepositoryServiceProvider.php ← interface→implementatsiya bog'lash
```

**Qoidalar:**
- Controller: validatsiya FormRequest'da, so'ng `$this->service->...()` chaqiradi, view/redirect/JSON qaytaradi. DB query yoki `if` biznes shartlari YO'Q.
- Service: bir nechta repozitoriyni birlashtiradi, tranzaksiya (`DB::transaction`), fayl saqlash, slug yasash kabi biznes ishlar.
- Repository: faqat ma'lumotга kirish (CRUD, query). Har biri Contracts'dagi interfeysni implement qiladi.
- Yangi modul = Model + migration + Interface + Repository + Service + Controller + FormRequest + Blade. Bog'lashni `RepositoryServiceProvider`ga qo'sh.

## Qo'llaniladigan design patternlar (qat'iy)

Quyidagi patternlar loyihada **standart**. Mos joyda ishlatilishi shart:

| Pattern | Qayerda ishlatiladi | Joylashuv |
|---------|---------------------|-----------|
| **Repository** | Barcha DB so'rovlari | `app/Repositories/{Contracts,Eloquent}` |
| **Service / Action** | Biznes logika (kitob yaratish, PDF yuklash). Bitta murakkab amal = Action | `app/Services`, `app/Actions` |
| **DTO** | Controller → Service ma'lumot uzatish (massiv `$data['x']` o'rniga tipli obyekt) | `app/Data` (yoki `app/DTO`) |
| **Form Request** | Har bir forma/so'rov validatsiyasi | `app/Http/Requests/{Admin,Client}` |
| **API Resource** | JSON chiqarish (ayniqsa live search) formati | `app/Http/Resources` |
| **Enum** | Sehrli stringlar o'rniga (kitob holati, til kodlari) | `app/Enums` |
| **Observer** | Model hodisalari (slug yasash, ko'rishlar soni) | `app/Observers` |
| **Pipeline** | Aqlli qidiruv/filtr (kategoriya+til+yil+saralash ketma-ket) | `app/Pipelines` (yoki query filtrlar) |
| **Policy / Gate** | Ruxsatlar (admin rollari bo'lsa) | `app/Policies` |

**Qoidalar:**
- Controller'dan Service'ga ma'lumot massiv emas, **DTO** orqali uzatiladi.
- Sehrli string/raqam ishlatma → **Enum**.
- Model ichida hodisaga bog'liq logika (slug, hisoblagich) → **Observer** (model'ni shishirma).
- Live search va boshqa JSON javoblar → har doim **API Resource** orqali formatlanadi.
- Murakkab filtr/qidiruv → **Pipeline** (uzun `if` zanjiri YO'Q).

**Hozir ISHLATILMAYDI** (ortiqcha murakkablik): CQRS, Event Sourcing, Decorator, microservices.
Kerak bo'lib qolsa, avval shu fayl yangilanadi.

## View tuzilmasi (qat'iy)

`resources/views/` ichида:

```
layouts/       admin.blade.php, guest.blade.php, client.blade.php
partials/      admin/  client/    ← takrorlanuvchi bo'laklar (sidebar, header, footer)
components/    *.blade.php (umumiy),  admin/  client/   ← <x-...> Blade komponentlari
pages/         admin/  client/  auth/   ← haqiqiy sahifalar (@extends layout)
errors/        maxsus xato sahifalari (404, 403, ...)
```

- Sahifa = `pages/{admin|client|auth}/...blade.php`, doim `@extends('layouts...')`.
- Takror bo'lak → `partials/`. Qayta ishlatiluvchi UI element → `components/` (`<x-admin.stat-card>`).
- Controller view'ni to'liq yo'l bilan chaqiradi: `view('pages.admin.dashboard')`.

## CSS / JS qoidalari

- **Tailwind utility klasslari HTML/Blade'da qoladi** — ularni .css faylga KO'CHIRMA (anti-pattern).
- Takror UI naqsh → **Blade komponentiga** (`<x-...>`) ajrat, kerak bo'lsa `@apply` (app.css).
- Alpine.js kichik atributlari (`x-data`, `@click`, `x-show`) Blade'da qoladi — bu idiomatik.
- Katta JS logika → `resources/js/` modullari. Inline `<script>` yozma.

## Kod izohlari (comments) — MAJBURIY: INGLIZ tilida

Kod ichidagi **barcha izohlar (comments) ingliz tilida** yoziladi — o'zbekcha EMAS.
Bu PHP (Controller, Service, Repository, Model, Migration, FormRequest, DTO, Enum,
Observer, routes/web.php), JS (`resources/js/`), Blade (`{{-- ... --}}`) va boshqa
hamma joyga tegishli. Docblock, inline `//`, blok `/* */`, Blade izohlar — hammasi inglizcha.

- **Foydalanuvchiga ko'rinadigan matn** (`{{ __('...') }}`, flash, validatsiya, label) — o'zbekcha qoladi (i18n bo'yicha).
- **Faqat dasturchi ko'radigan izoh** — inglizcha.
- Yangi kod yozganda ham, mavjud kodni tahrirlaganda ham shu qoidaga amal qil.

## Ko'p tillilik (i18n)

- 3 til: **O'zbek (uz, default)**, **Rus (ru)**, **Qoraqalpoq (kk)**. Ingliz YO'Q. (Qoraqalpoq uchun `kk` kodi — loyiha qarori.)
- **Admin panel — FAQAT o'zbekcha** (til almashtirgich yo'q; `SetLocale` admin'ни doim `uz`ga majburlaydi). Ko'p tillilik faqat **client sayt** uchun.
- Matnlar BOSHIDANOQ `{{ __('Tabiiy o\'zbekcha matn') }}` bilan yoziladi (retrofit qilinmaydi).
- Tarjima fayllari (`lang/ru.json`, `lang/kk.json`) loyiha OXIRIDA to'ldiriladi. `uz` default bo'lgani uchun kalit = o'zbekcha matn.

## Client sayt doirasi

- Hamma ko'radi: katalog, qidiruv, kitob sahifasi (login shart emas, SEO uchun server-render).
- O'qish (PDF.js) faqat login bilan; PDF controller orqali himoyalangan stream (yuklab olish YO'Q).
- Self-registration YO'Q — login/parolni admin beradi. Band qilish/profil/reyting YO'Q.

## Xavfsizlik (MAJBURIY — loyiha qattiq qo'riqlanadi)

Bu loyiha kutubxona tizimi — xavfsizlikка **juda katta e'tibor**. Har bir kod yozilганда quyidagilar shart:

- **Validatsiya:** har bir kirish (input) FormRequest orqali tekshiriladi. Ishonchsiz ma'lumot to'g'ridan-to'g'ri ishlatilmaydi.
- **Mass-assignment:** modelда `$fillable` aniq belgilanadi (`$guarded=[]` ishlatma). Faqat kerakli maydonlar.
- **Avtorizatsiya:** har bir admin amali `middleware`/`Policy`/`Gate` bilan himoyalanadi. Kutubxonachi-only maydonlar (inventar, narx, aktlar) server tomonda tekshiriladi — faqat Blade'да yashirish YETARLI EMAS.
- **SQL injection:** faqat Eloquent/Query Builder (bindings). Xom SQL string konkatenatsiya YO'Q.
- **XSS:** Blade `{{ }}` avtomatik escape qiladi. `{!! !!}` faqat ishonchli, tozalangan ma'lumotда.
- **CSRF:** barcha formalarда `@csrf`. State o'zgartiruvchi so'rovlar POST/PUT/DELETE.
- **Fayl yuklash:** mime-type, hajm, kengaytma tekshiriladi. Fayllar `storage/app` (public EMAS), controller orqali beriladi.
- **PDF/media himoyasi:** elektron kitob/audio to'g'ridan-to'g'ri URL bilan berilmaydi — faqat auth + policy tekshiruvidan o'tган controller stream orqali. Yuklab olish yo'q. (Kirish/chiqish aktlari endi fayl EMAS — oddiy matn maydonlari: akt raqami + sanasi/vaqti.)
- **Parol:** `Hash::make` (bcrypt/argon). Login'да rate limiting (`throttle`).
- **Kutubxonachi hujjatlari** (kirish/chiqish aktlari) — mijoz saytda MUTLAQO ko'rinmaydi.

## Ishlash va interaktivlik (performance — loyiha qotmasligi kerak)

Loyiha tez va uzluksiz ishlashi shart:

- **Server-render (Blade)** — kontent sahifalar (katalog, kitob sahifasi) SEO va tezlik uchun.
- **JS/AJAX** — interaktiv joylarда (live search, filtr, autocomplete, "ko'proq yuklash") **yengil JSON endpoint + Alpine.js `fetch`** ishlatiladi. Butun sahifa qayta yuklanmaydi. Livewire YO'Q.
- **N+1 oldini olish:** har doim `with()` (eager loading). Ro'yxatlarда `paginate()`.
- **Indekslar:** qidiriladigan/filtrlanadigan ustunларга (masalan `title`, FK'lar, `status`) DB indeks.
- **Aqilli qidiruv (HAM admin, HAM client):** real-time, imlo xatoni kechiradigan, relevantlik bo'yicha. **Laravel Scout + Meilisearch** (boshda MySQL FULLTEXT, Repository orqali oson o'tkaziladi). Admin=barcha kitob, client=ochiq katalog — bitta indeks, so'rov konteksti har xil. Real-time UI = Alpine `fetch` → JSON endpoint.
- **i18n DB darajasi:** lookup nomlari (kategoriya, tur, joylashuv, til) + nashriyot joyi → JSON tarjima (spatie/laravel-translatable); kitob title/annotation → bitta til (oddiy ustun, FULLTEXT); publisher/author → bitta qiymat. Translation jadval faqat katta jadvalда tarjima bo'yicha qidiruv/index kerak bo'lganда.
- **Og'ir JS** faqat kerak sahifада (masalan ApexCharts dashboardда), umumiy bundle'ni shishirma.
- **Keshlash** kerak bo'lganda (kategoriyalar daraxti kabi kam o'zgaradigan ma'lumot).

## Testlash (MAJBURIY)

- Backend kod (Service, Repository, Controller, migration, model) yozilgandan **so'ng ishlashini tekshirish SHART**. Claude o'zi yozgan kodni o'zi test qiladi — xatoni foydalanuvchiga yetkazishdan oldin topadi.
- Foydalanuvchi ham qo'lda test qiladi, lekin bu Claude'ning o'z-o'zini tekshirish majburiyatini bekor qilmaydi.
- Minimal tekshiruv: `php -l` (sintaksis), `php artisan migrate` o'tishi, `php artisan route:list`, `tinker` bilan model/relation/enum yuklanishi, sahifa render bo'lishi (`php artisan view:cache` yoki HTTP so'rov). Iloji bo'lsa avtomatik test (Pest/PHPUnit).
- Xato topilsa — tuzatiladi va qayta tekshiriladi.

## Buyruqlar

- `npm run dev` — Vite (CSS/JS jonli kompilyatsiya, hot reload). Saytни ko'rsatmaydi.
- Sayt: Laragon virtual host (`samvet-library.test`) yoki `php artisan serve` (localhost:8000).
- Production: `npm run build` (dev EMAS).

## Test login

`admin@samvet.uz` / `password` (DatabaseSeeder).
