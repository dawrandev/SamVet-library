<?php

namespace App\Services;

use App\Enums\BookFormat;
use App\Enums\CopyCondition;
use App\Enums\CopyStatus;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\BookType;
use App\Models\Language;
use App\Models\Location;
use App\Models\Publisher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Excel'dan kitoblarni import qiladi.
 *
 * Har qator = bitta NUSXA (book_copy). Bir xil kitobning bir nechta nusxasi
 * (inventar har xil) bitta Book'ga guruhlanadi. Lookuplar (turi/til/nashriyot/
 * joylashuv/muallif) yo'q bo'lsa avtomatik yaratiladi. Manba matn xom saqlanadi
 * (transliteratsiya yo'q); 3 tilli lookuplarga faqat `uz` yoziladi.
 *
 * @phpstan-type ImportStats array{books:int, copies:int, skipped:int, authors:int, book_types:int, languages:int, publishers:int, locations:int}
 */
class BookImportService
{
    /** Ustun indekslari (0-based) — "Kitob haqida" fayli tuzilmasi. */
    private const COL = [
        'udc' => 0,
        'author_mark' => 1,
        'title' => 2,
        'authors' => 3,
        'book_type' => 4,
        'format' => 5,
        'language' => 6,
        'pages' => 7,
        'publication_place' => 8,
        'publisher' => 9,
        'publication_year' => 10,
        'isbn' => 11,
        'annotation' => 12,
        'inventory_number' => 13,
        'location' => 14,
        'condition' => 15,
    ];

    /** @var array<string, int> Import statistikasi */
    private array $stats;

    /** @var array<string, int|null> Lookup keshi (takroriy so'rovni oldini oladi) */
    private array $lookupCache = [];

    /** @var callable|null Progress: fn(int $done, int $total): void */
    private $onProgress = null;

    public function onProgress(callable $callback): self
    {
        $this->onProgress = $callback;

        return $this;
    }

    /**
     * @return ImportStats
     */
    public function import(string $path): array
    {
        ini_set('memory_limit', '-1');

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $rows = $reader->load($path)->getActiveSheet()->toArray(null, true, false, false);

        $this->stats = [
            'books' => 0, 'copies' => 0, 'skipped' => 0,
            'authors' => 0, 'book_types' => 0, 'languages' => 0, 'publishers' => 0, 'locations' => 0,
        ];
        $this->lookupCache = [];

        $dataRows = array_slice($rows, 1); // sarlavha qatorini tashlaymiz
        $total = count($dataRows);

        foreach ($dataRows as $index => $row) {
            // Butunlay bo'sh qatorlarni umuman sanamaymiz (Excelda minglab bo'sh qator bo'ladi).
            if ($this->isEmptyRow($row)) {
                continue;
            }

            try {
                $this->importRow($row);
            } catch (\Throwable $e) {
                Log::warning('books:import '.($index + 2).'-qator xato: '.$e->getMessage());
                $this->stats['skipped']++;
            }

            if ($this->onProgress !== null) {
                ($this->onProgress)($index + 1, $total);
            }
        }

        return $this->stats;
    }

    /**
     * Bitta qatorni import qiladi: kitobni topib/yaratib, unga nusxa qo'shadi.
     *
     * @param  array<int, mixed>  $row
     */
    private function importRow(array $row): void
    {
        $title = $this->clean($row[self::COL['title']] ?? null);
        $inventory = $this->clean($row[self::COL['inventory_number']] ?? null);

        // Sarlavha yoki inventar bo'lmasa — bu haqiqiy nusxa emas.
        if ($title === null || $inventory === null) {
            $this->stats['skipped']++;

            return;
        }

        DB::transaction(function () use ($row, $title, $inventory) {
            $book = $this->resolveBook($row, $title);

            $this->attachAuthors($book, $this->clean($row[self::COL['authors']] ?? null));

            if ($this->createCopy($book, $row, $inventory)) {
                $this->stats['copies']++;
            } else {
                $this->stats['skipped']++; // bunday inventar allaqachon bor
            }
        });
    }

    /**
     * Kitobni identifikatsiya bo'yicha topadi yoki yaratadi.
     * Identity: sarlavha + muallif belgisi + UOK + nashr yili.
     *
     * @param  array<int, mixed>  $row
     */
    private function resolveBook(array $row, string $title): Book
    {
        $authorMark = $this->clean($row[self::COL['author_mark']] ?? null);
        $udc = $this->clean($row[self::COL['udc']] ?? null);
        $year = $this->parseYear($this->clean($row[self::COL['publication_year']] ?? null));

        // where('col', null) Laravel'da avtomatik whereNull bo'ladi — null'lar to'g'ri solishtiriladi.
        $book = Book::query()
            ->where('title', $title)
            ->where('author_mark', $authorMark)
            ->where('udc', $udc)
            ->where('publication_year', $year)
            ->first();

        if ($book !== null) {
            return $book;
        }

        $book = Book::create([
            'title' => $title,
            'author_mark' => $authorMark,
            'udc' => $udc,
            'publication_year' => $year,
            'book_type_id' => $this->translatableLookup(BookType::class, 'book_types', $this->clean($row[self::COL['book_type']] ?? null)),
            'language_id' => $this->translatableLookup(Language::class, 'languages', $this->clean($row[self::COL['language']] ?? null)),
            'publisher_id' => $this->plainLookup(Publisher::class, 'publishers', $this->clean($row[self::COL['publisher']] ?? null)),
            'pages' => $this->parseInt($this->clean($row[self::COL['pages']] ?? null)),
            'publication_place' => $this->placeTranslation($this->clean($row[self::COL['publication_place']] ?? null)),
            'isbn' => $this->cleanIsbn($this->clean($row[self::COL['isbn']] ?? null)),
            'annotation' => $this->clean($row[self::COL['annotation']] ?? null),
        ]); // slug — BookObserver

        $this->stats['books']++;

        return $book;
    }

    /**
     * "Muallif1, Muallif2" satrini bo'lib, har birini topib/yaratib kitobga bog'laydi.
     */
    private function attachAuthors(Book $book, ?string $authorsString): void
    {
        if ($authorsString === null) {
            return;
        }

        $ids = [];
        foreach (preg_split('/[,;]+/', $authorsString) ?: [] as $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }

            $author = Author::firstOrCreate(['name' => $name]);
            if ($author->wasRecentlyCreated) {
                $this->stats['authors']++;
            }
            $ids[] = $author->id;
        }

        if ($ids !== []) {
            $book->authors()->syncWithoutDetaching($ids);
        }
    }

    /**
     * Nusxa yaratadi. Inventar noyob — allaqachon bo'lsa yaratmaydi (idempotent).
     *
     * @param  array<int, mixed>  $row
     */
    private function createCopy(Book $book, array $row, string $inventory): bool
    {
        if (BookCopy::where('inventory_number', $inventory)->exists()) {
            return false;
        }

        BookCopy::create([
            'book_id' => $book->id,
            'inventory_number' => $inventory,
            'format' => $this->mapFormat($this->clean($row[self::COL['format']] ?? null)),
            'condition' => $this->mapCondition($this->clean($row[self::COL['condition']] ?? null)),
            'status' => CopyStatus::Available->value,
            'location_id' => $this->translatableLookup(Location::class, 'locations', $this->clean($row[self::COL['location']] ?? null)),
        ]);

        return true;
    }

    // --- Lookup yordamchilari ---

    /**
     * 3 tilli lookup (turi/til/joylashuv): `name->uz` bo'yicha topadi yoki `uz` bilan yaratadi.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    private function translatableLookup(string $modelClass, string $group, ?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $key = $group.'|'.$value;
        if (array_key_exists($key, $this->lookupCache)) {
            return $this->lookupCache[$key];
        }

        $model = $modelClass::where('name->uz', $value)->first();
        if ($model === null) {
            $model = $modelClass::create(['name' => ['uz' => $value]]);
            $this->stats[$group]++;
        }

        return $this->lookupCache[$key] = $model->id;
    }

    /**
     * Bir qiymatli lookup (nashriyot): oddiy `name` bo'yicha topadi/yaratadi.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    private function plainLookup(string $modelClass, string $group, ?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $key = $group.'|'.$value;
        if (array_key_exists($key, $this->lookupCache)) {
            return $this->lookupCache[$key];
        }

        $model = $modelClass::firstOrCreate(['name' => $value]);
        if ($model->wasRecentlyCreated) {
            $this->stats[$group]++;
        }

        return $this->lookupCache[$key] = $model->id;
    }

    // --- Xaritalar (enum) ---

    private function mapFormat(?string $value): string
    {
        $n = mb_strtolower((string) $value, 'UTF-8');

        // "Bosma" bo'lsa — jismoniy nusxa (garchi "Bosma, Elektron" bo'lsa ham inventarli jismoniy nusxa).
        return match (true) {
            str_contains($n, 'bosma') => BookFormat::Print->value,
            str_contains($n, 'elektron') => BookFormat::Electronic->value,
            str_contains($n, 'brayl'), str_contains($n, 'braille') => BookFormat::Braille->value,
            default => BookFormat::Print->value,
        };
    }

    private function mapCondition(?string $value): ?string
    {
        $n = $this->stripApostrophes(mb_strtolower((string) $value, 'UTF-8'));

        return match (true) {
            $n === '' || $n === 'yoq' => null, // "Yo'q" — elektron nusxada holat yo'q
            str_contains($n, 'eski') => CopyCondition::Old->value,
            str_contains($n, 'yangi') => CopyCondition::New->value,
            str_contains($n, 'yirtil') => CopyCondition::Torn->value,
            str_contains($n, 'tuzat'), str_contains($n, 'tamir') => CopyCondition::Repaired->value,
            str_contains($n, 'chizil') => CopyCondition::Scribbled->value,
            default => null,
        };
    }

    // --- Tozalash / parslash ---

    /**
     * Qator butunlay bo'shmi (barcha kataklar bo'sh).
     *
     * @param  array<int, mixed>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function parseInt(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        return preg_match('/\d+/', $value, $m) === 1 ? (int) $m[0] : null;
    }

    private function parseYear(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        return preg_match('/(1\d{3}|20\d{2})/', $value, $m) === 1 ? (int) $m[1] : null;
    }

    /**
     * @return array<string, string>|null  {uz: ...} yoki null
     */
    private function placeTranslation(?string $value): ?array
    {
        return $value === null ? null : ['uz' => $value];
    }

    /**
     * ISBN: "Yo'q" (yo'q so'zi) bo'lsa null, aks holda xom qiymat.
     */
    private function cleanIsbn(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $this->stripApostrophes(mb_strtolower($value, 'UTF-8')) === 'yoq' ? null : $value;
    }

    private function stripApostrophes(string $value): string
    {
        return trim(str_replace(["'", '‘', '’', '`', 'ʻ', 'ʼ'], '', $value));
    }
}
