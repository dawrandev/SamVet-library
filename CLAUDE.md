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

## Ko'p tillilik (i18n)

- 3 til: **O'zbek (uz, default)**, **Rus (ru)**, **Qoraqalpoq (kaa)**. Ingliz YO'Q.
- Matnlar BOSHIDANOQ `{{ __('Tabiiy o\'zbekcha matn') }}` bilan yoziladi (retrofit qilinmaydi).
- Tarjima fayllari (`lang/ru.json`, `lang/kaa.json`) loyiha OXIRIDA to'ldiriladi. `uz` default bo'lgani uchun kalit = o'zbekcha matn.

## Client sayt doirasi

- Hamma ko'radi: katalog, qidiruv, kitob sahifasi (login shart emas, SEO uchun server-render).
- O'qish (PDF.js) faqat login bilan; PDF controller orqali himoyalangan stream (yuklab olish YO'Q).
- Self-registration YO'Q — login/parolni admin beradi. Band qilish/profil/reyting YO'Q.

## Buyruqlar

- `npm run dev` — Vite (CSS/JS jonli kompilyatsiya, hot reload). Saytни ko'rsatmaydi.
- Sayt: Laragon virtual host (`samvet-library.test`) yoki `php artisan serve` (localhost:8000).
- Production: `npm run build` (dev EMAS).

## Test login

`admin@samvet.uz` / `password` (DatabaseSeeder).
