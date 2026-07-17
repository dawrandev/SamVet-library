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
use App\Models\PublicationPlace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Imports books from Excel.
 *
 * Each row = one COPY (book_copy). Multiple copies of the same book
 * (with different inventory numbers) are grouped under one Book. Lookups (type/language/publication place/
 * location/author) are created automatically if missing. Source text is stored raw
 * (no transliteration); only `uz` is written to the 3-language lookups.
 *
 * @phpstan-type ImportStats array{books:int, copies:int, skipped:int, authors:int, book_types:int, languages:int, publication_places:int, locations:int}
 */
class BookImportService
{
    /** Column indexes (0-based) — structure of the "Kitob haqida" file. */
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

    /** @var array<string, int> Import statistics */
    private array $stats;

    /** @var array<string, int|null> Lookup cache (avoids repeated queries) */
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
            'authors' => 0, 'book_types' => 0, 'languages' => 0, 'publication_places' => 0, 'locations' => 0,
        ];
        $this->lookupCache = [];

        $dataRows = array_slice($rows, 1); // drop the header row
        $total = count($dataRows);

        foreach ($dataRows as $index => $row) {
            // Do not count fully empty rows at all (Excel can have thousands of blank rows).
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
     * Imports a single row: finds/creates the book and adds a copy to it.
     *
     * @param  array<int, mixed>  $row
     */
    private function importRow(array $row): void
    {
        $title = $this->clean($row[self::COL['title']] ?? null);
        $inventory = $this->clean($row[self::COL['inventory_number']] ?? null);

        // Without a title or inventory number — this is not a real copy.
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
                $this->stats['skipped']++; // this inventory number already exists
            }
        });
    }

    /**
     * Finds or creates the book by its identity.
     * Identity: title + author mark + UDC + publication year.
     *
     * @param  array<int, mixed>  $row
     */
    private function resolveBook(array $row, string $title): Book
    {
        $authorMark = $this->clean($row[self::COL['author_mark']] ?? null);
        $udc = $this->clean($row[self::COL['udc']] ?? null);
        $year = $this->parseYear($this->clean($row[self::COL['publication_year']] ?? null));

        // where('col', null) becomes an automatic whereNull in Laravel — nulls are compared correctly.
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
            'language_id' => $this->resolveLanguage($this->clean($row[self::COL['language']] ?? null)),
            'publisher' => $this->clean($row[self::COL['publisher']] ?? null),
            'pages' => $this->parseInt($this->clean($row[self::COL['pages']] ?? null)),
            'publication_place_id' => $this->translatableLookup(PublicationPlace::class, 'publication_places', $this->clean($row[self::COL['publication_place']] ?? null)),
            'isbn' => $this->cleanIsbn($this->clean($row[self::COL['isbn']] ?? null)),
            'annotation' => $this->clean($row[self::COL['annotation']] ?? null),
        ]); // slug — BookObserver

        $this->stats['books']++;

        return $book;
    }

    /**
     * Splits a "Author1, Author2" string, finds/creates each one, and links them to the book.
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
     * Creates a copy. The inventory number is unique — does not create if it already exists (idempotent).
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

    // --- Lookup helpers ---

    /**
     * 3-language lookup (type/language/location): finds by `name->uz` or creates with `uz`.
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
     * Resolve the language lookup, guarding against format words that some
     * source rows put in the language column (e.g. "Bosma"/"Elektron"). Those are
     * copy formats, never languages — so we leave the language empty instead of
     * polluting the languages lookup.
     */
    private function resolveLanguage(?string $value): ?int
    {
        if ($value !== null && $this->isFormatWord($value)) {
            return null;
        }

        return $this->translatableLookup(Language::class, 'languages', $value);
    }

    private function isFormatWord(string $value): bool
    {
        $n = $this->stripApostrophes(mb_strtolower($value, 'UTF-8'));

        return str_contains($n, 'bosma')
            || str_contains($n, 'elektron')
            || str_contains($n, 'brayl')
            || str_contains($n, 'braille');
    }

    // --- Maps (enum) ---

    private function mapFormat(?string $value): string
    {
        $n = mb_strtolower((string) $value, 'UTF-8');

        // If it is "Bosma" — a physical copy (even "Bosma, Elektron" is still an inventoried physical copy).
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
            $n === '' || $n === 'yoq' => null, // "Yo'q" (none) — an electronic copy has no condition
            str_contains($n, 'eski') => CopyCondition::Old->value,
            str_contains($n, 'yangi') => CopyCondition::New->value,
            str_contains($n, 'yirtil') => CopyCondition::Torn->value,
            str_contains($n, 'tuzat'), str_contains($n, 'tamir') => CopyCondition::Repaired->value,
            str_contains($n, 'chizil') => CopyCondition::Scribbled->value,
            default => null,
        };
    }

    // --- Cleaning / parsing ---

    /**
     * Whether the row is entirely empty (all cells blank).
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
     * ISBN: null if it is "Yo'q" (the word "none"), otherwise the raw value.
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
