<?php

namespace App\Services;

use App\Enums\Gender;
use App\Enums\ReaderStatus;
use App\Models\AffiliationGroup;
use App\Models\AffiliationPlace;
use App\Models\AffiliationUnit;
use App\Models\District;
use App\Models\Reader;
use App\Models\ReaderType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Import library readers from a large multi-sheet Excel file.
 *
 * Each sheet is loaded separately (to avoid loading the full 76MB), images are not read.
 * Idempotent: updateOrCreate by id_number or pinfl.
 *
 * @phpstan-type SheetStat array{imported:int, updated:int, skipped:int, photos:int, type:?string}
 */
class ReaderImportService
{
    /**
     * "Regular" sheets to import → reader_types.name (resolved to an id via
     * {@see typeId()}). In these sheets status = active. Texnikum sheets
     * (TT/TO/TX) are no longer imported as their own type — that taxonomy
     * was dropped from reader_types entirely.
     *
     * @var array<string, string>
     */
    private const SHEET_TYPES = [
        'BT' => 'Bakalavr talabasi (kunduzgi)',
        'MT' => 'Magistr talabasi',
        'DT' => 'Doktorant',
        'PO' => 'Professor-o‘qituvchi',
        'FX' => 'Filial xodimi',
    ];

    /**
     * ST sheet — columns are unnamed, mapped by POSITION. type=bachelor, dedup=pinfl.
     * Separate (NOT in SHEET_TYPES) because the header map does not work.
     */
    private const SHEET_ST = 'ST';

    /**
     * "Ketkenler" sheet — import all, status=left, type from the ID prefix.
     */
    private const SHEET_LEFT = 'Ketkenler';

    /**
     * Sheets to skip entirely.
     *
     * @var list<string>
     */
    private const SKIP_SHEETS = [
        'Tusindirme',
        'Pechat 1 kurs',
        'Pechat 2 kurs',
        'Pechat 3 kurs',
        'Pechat 4 kurs',
        'Pechat PO',
        'Pechat FX',
        'Pechat suwret',
        'ID nomer',
    ];

    /**
     * ID prefix (e.g. "BT" -> Bakalavr talabasi (kunduzgi)) — used to determine
     * type in the Ketkenler sheet. Same dropped-texnikum note as SHEET_TYPES.
     *
     * @var array<string, string>
     */
    private const ID_PREFIX_TYPES = [
        'BT' => 'Bakalavr talabasi (kunduzgi)',
        'MT' => 'Magistr talabasi',
        'DT' => 'Doktorant',
        'PO' => 'Professor-o‘qituvchi',
        'FX' => 'Filial xodimi',
    ];

    /**
     * ST sheet positional column indexes (0-based).
     *
     * @var array<string, int>
     */
    private const ST_POSITIONS = [
        'full_name' => 3,
        'affiliation_unit' => 5,
        'affiliation_group' => 6,
        'nationality' => 7,
        'birth_date' => 8,
        'passport' => 9,
        'pinfl' => 10,
        'gender' => 11,
        'district' => 12,
        'address' => 13,
        'phone' => 14,
        'member_year' => 15,
    ];

    /** @var callable|null Progress callback: fn(string $sheet, string $message): void */
    private $onSheet = null;

    /** @var Collection<string, int>|null Lazily-resolved reader_types.name => id, see typeId(). */
    private ?Collection $typeIdsByName = null;

    public function __construct(
        private readonly ReaderPhotoExtractor $photos = new ReaderPhotoExtractor(),
    ) {}

    /** Resolves a reader_types.name to its id (cached for the whole import run). */
    private function typeId(string $name): ?int
    {
        $this->typeIdsByName ??= ReaderType::query()->pluck('id', 'name');

        return $this->typeIdsByName[$name] ?? null;
    }

    /** Reverse of typeId() — for the progress-stats display only. */
    private function typeName(?int $id): ?string
    {
        if ($id === null) {
            return null;
        }

        $this->typeIdsByName ??= ReaderType::query()->pluck('id', 'name');

        return $this->typeIdsByName->flip()[$id] ?? null;
    }

    public function onSheet(callable $callback): self
    {
        $this->onSheet = $callback;

        return $this;
    }

    /**
     * Invokes the progress callback (if set).
     */
    private function report(string $sheet, string $message): void
    {
        if ($this->onSheet !== null) {
            ($this->onSheet)($sheet, $message);
        }
    }

    /**
     * Imports the entire file.
     *
     * @return array<string, SheetStat> Sheet name => stats
     */
    public function import(string $path): array
    {
        ini_set('memory_limit', '-1');

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        // Get the sheet names without loading the whole file.
        $sheetNames = $reader->listWorksheetNames($path);

        $stats = [];

        foreach ($sheetNames as $sheetName) {
            // The sheet name sometimes has extra whitespace (e.g. "TT ") — trim to match
            $key = trim($sheetName);

            if (in_array($key, self::SKIP_SHEETS, true)) {
                continue;
            }

            $context = $this->resolveSheet($key);
            if ($context === null) {
                continue; // non-importable / unknown sheet
            }

            $this->report($sheetName, 'boshlandi');

            try {
                $stats[$sheetName] = $this->importSheet($path, $sheetName, $context);
            } catch (\Throwable $e) {
                Log::error("readers:import — '{$sheetName}' varag'i o'qishda xato: {$e->getMessage()}");
                $stats[$sheetName] = ['imported' => 0, 'updated' => 0, 'skipped' => 0, 'photos' => 0, 'type' => 'XATO'];
            }
        }

        return $stats;
    }

    /**
     * Determines the import context (mode + default type id + status) from the sheet name.
     *
     * @return array{mode:string, type:?int, status:ReaderStatus}|null
     */
    private function resolveSheet(string $sheetName): ?array
    {
        if ($sheetName === self::SHEET_ST) {
            return ['mode' => 'st', 'type' => $this->typeId('Bakalavr talabasi (kunduzgi)'), 'status' => ReaderStatus::Active];
        }

        if ($sheetName === self::SHEET_LEFT) {
            return ['mode' => 'left', 'type' => null, 'status' => ReaderStatus::Left];
        }

        if (isset(self::SHEET_TYPES[$sheetName])) {
            return ['mode' => 'header', 'type' => $this->typeId(self::SHEET_TYPES[$sheetName]), 'status' => ReaderStatus::Active];
        }

        return null;
    }

    /**
     * Loads a single sheet and imports its rows.
     *
     * @param  array{mode:string, type:?int, status:ReaderStatus}  $context
     * @return SheetStat
     */
    private function importSheet(string $path, string $sheetName, array $context): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly([$sheetName]);

        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $photoCount = 0;

        $rows = $sheet->toArray(null, true, false, false);

        // Empty sheet
        if (count($rows) === 0) {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            return ['imported' => 0, 'updated' => 0, 'skipped' => 0, 'photos' => 0, 'type' => $this->typeName($context['type'])];
        }

        // ST — positional; others — header map.
        $columnMap = $context['mode'] === 'st'
            ? self::ST_POSITIONS
            : $this->buildHeaderMap($rows[0]);

        // Images in the sheet: absolute row index (0-based) => image.
        $sheetPhotos = $this->photos->photosForSheet($path, $sheetName);

        // Skip the header row.
        $dataRows = array_slice($rows, 1);

        foreach ($dataRows as $index => $row) {
            // dataRows[$index] = rows[$index + 1] (absolute index) — matches the image anchor.
            $photo = $sheetPhotos[$index + 1] ?? null;

            try {
                $result = $this->importRow($row, $columnMap, $context, $photo);
            } catch (\Throwable $e) {
                $rowNo = $index + 2; // 1-based + header
                Log::warning("readers:import — '{$sheetName}' {$rowNo}-qator xato: {$e->getMessage()}");
                $result = 'skipped';
            }

            match ($result) {
                'imported' => $imported++,
                'updated' => $updated++,
                default => $skipped++,
            };

            if ($photo !== null && $result !== 'skipped') {
                $photoCount++;
            }
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return ['imported' => $imported, 'updated' => $updated, 'skipped' => $skipped, 'photos' => $photoCount, 'type' => $this->typeName($context['type'])];
    }

    /**
     * Builds a header -> column index (0-based) map from the header row.
     * Header variants across different sheets are normalized.
     *
     * @param  array<int, mixed>  $headerRow
     * @return array<string, int>
     */
    private function buildHeaderMap(array $headerRow): array
    {
        // normalize(header) => field
        $aliases = [
            'idraqam' => 'id_number',
            'registraciyaraqami' => 'registration_number',
            'registratsiyaraqami' => 'registration_number',
            'berilgansana' => 'issued_date',
            'toliqismi' => 'full_name',
            'oqishjoyi' => 'affiliation_place',
            'ishjoyi' => 'affiliation_place',
            'ishjoyioqishjoyi' => 'affiliation_place',
            'mutaxasisligi' => 'affiliation_unit',
            'mutaxassisligi' => 'affiliation_unit',
            'bolimi' => 'affiliation_unit',
            'bolimimutaxassisligi' => 'affiliation_unit',
            'guruhi' => 'affiliation_group',
            'lavozimi' => 'affiliation_group',
            'lavozimiguruhi' => 'affiliation_group',
            'millati' => 'nationality',
            'tugilgansanasi' => 'birth_date',
            'pasport' => 'passport',
            'jshshr' => 'pinfl',
            'jinsi' => 'gender',
            'tuman' => 'district',
            'manzil' => 'address',
            'telefon' => 'phone',
            'azobolganyili' => 'member_year',
            'azobolganyil' => 'member_year',
            'boshqakutubxonalargaazolik' => 'other_library_member',
            'izoh' => 'note',
        ];

        $map = [];

        foreach ($headerRow as $index => $rawHeader) {
            if ($rawHeader === null || $rawHeader === '') {
                continue;
            }

            $key = $this->normalizeHeader((string) $rawHeader);

            if ($key !== '' && isset($aliases[$key]) && ! isset($map[$aliases[$key]])) {
                $map[$aliases[$key]] = $index;
            }
        }

        return $map;
    }

    /**
     * Normalize a header for comparison: lowercase, no whitespace/punctuation,
     * Uzbek special letters simplified (o' -> o, g' -> g, ' -> removed).
     */
    private function normalizeHeader(string $value): string
    {
        $value = trim($value);
        $value = mb_strtolower($value, 'UTF-8');

        // Remove o'/o' variants and apostrophes
        $value = str_replace(
            ["o‘", "o'", "o`", "g‘", "g'", "g`", '‘', '’', '`', "'"],
            ['o', 'o', 'o', 'g', 'g', 'g', '', '', '', ''],
            $value
        );

        // Remove everything that is not a letter/digit (whitespace, slash, dot)
        $value = preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?? '';

        return $value;
    }

    /**
     * Cleans, dedups, and saves a single row.
     *
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $columnMap  field => 0-based column index
     * @param  array{mode:string, type:?int, status:ReaderStatus}  $context
     * @param  array{bytes:string, ext:string}|null  $photo  image attached to this row
     * @return 'imported'|'updated'|'skipped'
     */
    private function importRow(array $row, array $columnMap, array $context, ?array $photo = null): string
    {
        $get = function (string $field) use ($row, $columnMap): ?string {
            if (! isset($columnMap[$field])) {
                return null;
            }
            $value = $row[$columnMap[$field]] ?? null;

            return $this->cleanString($value);
        };

        $fullName = $get('full_name');
        $idNumber = $get('id_number');
        $passport = $get('passport');
        $pinfl = $this->cleanPinfl($get('pinfl'));

        // Skip group-header / empty rows.
        if (! $this->isPersonRow($fullName, $idNumber, $passport, $pinfl)) {
            return 'skipped';
        }

        // A dedup key is required: id_number or pinfl.
        if ($idNumber === null && $pinfl === null) {
            return 'skipped';
        }

        // Determine type: in Ketkenler from the ID prefix; otherwise the context type.
        $type = $context['type'];
        if ($context['mode'] === 'left') {
            $type = $this->typeFromIdNumber($idNumber);
            if ($type === null) {
                return 'skipped'; // prefix not found -> drop
            }
        }

        $attributes = [
            'reader_type_id' => $type,
            'status' => $context['status'],
            'full_name' => $fullName,
            'registration_number' => $get('registration_number'),
            'issued_date' => $this->parseDate($this->rawValue($row, $columnMap, 'issued_date')),
            'affiliation_place_id' => $this->resolveLookupId(AffiliationPlace::class, $get('affiliation_place')),
            'affiliation_unit_id' => $this->resolveLookupId(AffiliationUnit::class, $get('affiliation_unit')),
            'affiliation_group_id' => $this->resolveLookupId(AffiliationGroup::class, $get('affiliation_group')),
            'nationality' => $get('nationality'),
            'birth_date' => $this->parseDate($this->rawValue($row, $columnMap, 'birth_date')),
            'passport' => $passport,
            'pinfl' => $pinfl,
            'gender' => $this->parseGender($get('gender')),
            'district_id' => $this->resolveLookupId(District::class, $get('district')),
            'address' => $get('address'),
            'phone' => $get('phone'),
            'member_year' => $this->parseYear($get('member_year')),
            'other_library_member' => $get('other_library_member'),
            'note' => $get('note'),
        ];

        // If id_number exists it is the key, otherwise pinfl.
        if ($idNumber !== null) {
            $attributes['id_number'] = $idNumber;
            $reader = $this->upsert(['id_number' => $idNumber], $attributes);
            $key = $idNumber;
        } else {
            $attributes['pinfl'] = $pinfl;
            $reader = $this->upsert(['pinfl' => $pinfl], $attributes);
            $key = (string) $pinfl;
        }

        if ($photo !== null) {
            $this->attachPhoto($reader, $key, $photo);
        }

        return $reader->wasRecentlyCreated ? 'imported' : 'updated';
    }

    /**
     * @param  array<string, mixed>  $keys
     * @param  array<string, mixed>  $attributes
     */
    private function upsert(array $keys, array $attributes): Reader
    {
        return Reader::updateOrCreate($keys, $attributes);
    }

    /**
     * Resolves a free-text lookup value (affiliation place/unit/group, district)
     * to its id, creating the lookup row on first sight — same "text -> lookup"
     * normalization the one-time backfill migration did for pre-existing data.
     *
     * @param  class-string<Model>  $modelClass
     */
    private function resolveLookupId(string $modelClass, ?string $name): ?int
    {
        if ($name === null) {
            return null;
        }

        return $modelClass::query()->firstOrCreate(['name' => $name])->id;
    }

    /**
     * Saves the image to the public disk and sets reader.photo.
     * The file name is deterministic (by key) — a re-import overwrites it, leaving no orphans.
     *
     * @param  array{bytes:string, ext:string}  $photo
     */
    private function attachPhoto(Reader $reader, string $key, array $photo): void
    {
        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '', $key);
        $safe = $safe !== '' ? $safe : (string) $reader->id;

        $path = 'readers/photos/' . $safe . '.' . $photo['ext'];

        Storage::disk('public')->put($path, $photo['bytes']);

        if ($reader->photo !== $path) {
            $reader->forceFill(['photo' => $path])->save();
        }
    }

    /**
     * Whether the row is a real person (not a group-header or empty).
     */
    private function isPersonRow(?string $fullName, ?string $idNumber, ?string $passport, ?string $pinfl): bool
    {
        if ($fullName === null || $fullName === '') {
            return false;
        }

        // If full_name is a group header like kurs/magistr/doktorant/bosqich (without id/pinfl) — skip.
        $looksLikeGroupHeader = preg_match('/kurs|magistr|doktorant|bosqich/i', $fullName) === 1;

        $hasIdNumber = $idNumber !== null && preg_match('/^[A-Za-z]{2}\d+/', $idNumber) === 1;
        $hasPinfl = $pinfl !== null && strlen($pinfl) >= 10;
        $hasPassport = $passport !== null && preg_match('/^[A-Za-z]{2}\d+/', $passport) === 1;

        if ($looksLikeGroupHeader && ! $hasIdNumber && ! $hasPinfl && ! $hasPassport) {
            return false;
        }

        return $hasIdNumber || $hasPinfl || $hasPassport;
    }

    /**
     * ReaderType id from the ID number prefix (BT, MT, ...).
     */
    private function typeFromIdNumber(?string $idNumber): ?int
    {
        if ($idNumber === null) {
            return null;
        }

        if (preg_match('/^([A-Za-z]{2})/', $idNumber, $m) !== 1) {
            return null;
        }

        $name = self::ID_PREFIX_TYPES[strtoupper($m[1])] ?? null;

        return $name !== null ? $this->typeId($name) : null;
    }

    /**
     * Gets the raw cell value (without cleaning) — needed for date parsing of a serial number.
     *
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $columnMap
     */
    private function rawValue(array $row, array $columnMap, string $field): mixed
    {
        if (! isset($columnMap[$field])) {
            return null;
        }

        return $row[$columnMap[$field]] ?? null;
    }

    private function cleanString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * PINFL: digits only; keep if length is 12-14.
     */
    private function cleanPinfl(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $value) ?? '';
        $len = strlen($digits);

        return ($len >= 12 && $len <= 14) ? $digits : null;
    }

    /**
     * Date: Excel serial (numeric) or a "dd.mm.yyyy" string. Null if it is only a year.
     */
    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Excel serial number
        if (is_numeric($value)) {
            $num = (float) $value;

            // Only a year (e.g. 1995) — not a date.
            if ($num >= 1000 && $num <= 9999) {
                return null;
            }

            try {
                $dt = ExcelDate::excelToDateTimeObject($num);

                return $dt->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        $str = trim((string) $value);

        // Only a year
        if (preg_match('/^\d{4}$/', $str) === 1) {
            return null;
        }

        // dd.mm.yyyy / dd-mm-yyyy / dd/mm/yyyy
        if (preg_match('#^(\d{1,2})[.\-/](\d{1,2})[.\-/](\d{4})$#', $str, $m) === 1) {
            $day = (int) $m[1];
            $month = (int) $m[2];
            $year = (int) $m[3];

            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }

            return null;
        }

        // yyyy-mm-dd
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $str, $m) === 1) {
            $year = (int) $m[1];
            $month = (int) $m[2];
            $day = (int) $m[3];

            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        return null;
    }

    /**
     * member_year: extracts a 4-digit year and returns it as an int.
     */
    private function parseYear(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (preg_match('/(19|20)\d{2}/', $value, $m) === 1) {
            return (int) $m[0];
        }

        return null;
    }

    /**
     * gender: 'Erkak' -> male, 'Ayol' -> female.
     */
    private function parseGender(?string $value): ?Gender
    {
        if ($value === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($value), 'UTF-8');

        return match (true) {
            str_contains($normalized, 'erkak'), str_contains($normalized, 'мужчина'), $normalized === 'm' => Gender::Male,
            str_contains($normalized, 'ayol'), str_contains($normalized, 'женщина'), $normalized === 'f' => Gender::Female,
            default => null,
        };
    }
}
