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

```bash
php artisan dusk         # Chrome kerak; ChromeDriver avtomatik
```
