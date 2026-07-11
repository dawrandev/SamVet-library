# Testlar

Loyiha avtomatlashtirilgan testlar bilan qoplanadi: **Unit** (sof mantiq),
**Feature/Integration** (HTTP + DB) va **E2E** (Laravel Dusk — brauzer).

## Sozlash (bir marta)

Testlar **alohida** MySQL bazasida ishlaydi — haqiqiy `samvet-library` bazasiga
**tegmaydi** va **productionga chiqmaydi**. Faqat lokal/CI vositasi.

```sql
CREATE DATABASE samvet_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

`phpunit.xml` allaqachon shu bazaga (`DB_DATABASE=samvet_test`) yo'naltirilgan.
`RefreshDatabase` har testda uni migratsiya qiladi.

## Ishga tushirish

```bash
composer test            # yoki: vendor/bin/pest
vendor/bin/pest --filter=Reader   # nom bo'yicha filtrlash
vendor/bin/pest tests/Feature     # faqat feature
```

## Tuzilma

```
tests/
├── Pest.php            # TestCase + RefreshDatabase bog'lanishi, helperlar
├── Unit/               # DB'siz sof mantiq (DTO, enum, observer)
├── Feature/            # HTTP + DB (auth, reader, katalog, CRUD, validatsiya)
└── Browser/            # Dusk e2e (PDF reader, Alpine filtrlar, upload)
```

Helperlar (`tests/Pest.php`): `actingAsAdmin()`, `actingAsReader()`.

## Dusk (E2E)

Dusk **jonli** serverga qarshi ishlaydi, shu sabab u ham `samvet_test` bazasidan
foydalanadi (`.env.dusk.local` — gitignore'da). Ketma-ketlik:

```bash
# 1) test bazasini tayyorla
DB_DATABASE=samvet_test php artisan migrate:fresh --force

# 2) test env bilan serverni yoq (alohida terminalda)
php artisan serve --env=dusk.local --port=8001

# 3) brauzer testlarini ishga tushir
php artisan dusk         # Chrome kerak; ChromeDriver vendor/laravel/dusk/bin da
```

`.env.dusk.local` da: `DB_DATABASE=samvet_test`, `APP_URL=http://127.0.0.1:8001`.
Chrome versiyasi yangilансa, mos ChromeDriver'ni `vendor/laravel/dusk/bin/` ga
qo'ying (PHP CLI'da CA sertifikat muammosi bo'lsa `php artisan dusk:chrome-driver`
ishlamasligi mumkin — qo'lda yuklab qo'yiladi).
