<?php

namespace App\Services;

use App\Enums\Gender;
use App\Enums\ReaderImportOutcome;
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
 * @phpstan-type SheetStat array{imported:int, updated:int, skipped:int, photos:int, type:?string, issues:array<string,int>, missing_columns:array<int,string>, headers:array<int,string>, error?:string}
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

    /** @var callable|null Progress callback: fn(string, string): void */
    private $onSheet = null;

    /** @var Collection<string, int>|null Lazily-resolved reader_types.name => id, see typeId(). */
    private ?Collection $typeIdsByName = null;

    public function __construct(
        private readonly ReaderPhotoExtractor $photos = new ReaderPhotoExtractor,
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
        // NOTE: memory_limit is deliberately NOT raised here.
        //
        // This used to be ini_set('memory_limit', '-1'), which did not make the
        // import cheaper — it only removed the one thing that would have
        // reported the cost. With no ceiling PHP never stops itself, so the
        // host's own limit is what ends the request: the worker is killed
        // outright, Apache answers a bare 503, and nothing reaches Laravel's
        // log. A real ceiling turns the same failure into an "Allowed memory
        // size exhausted" error with a stack trace and a log line.
        //
        // Staying inside the server's budget is now the importer's job, not
        // the ini setting's: images are read one row at a time (SheetPhotos)
        // rather than all at once, which is what made the old code need an
        // unbounded heap in the first place.

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
                $stats[$sheetName] = [
                    'imported' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'photos' => 0,
                    'type' => 'XATO',
                    'issues' => [],
                    'missing_columns' => [],
                    'headers' => [],
                    // Shown on screen: a sheet that could not be read at all is
                    // a different failure from one whose rows were skipped, and
                    // the two used to be indistinguishable in the results table.
                    'error' => $e->getMessage(),
                ];
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

            return $this->sheetStat($context, 0, 0, 0, 0);
        }

        // ST — positional; others — header map.
        $columnMap = $context['mode'] === 'st'
            ? self::ST_POSITIONS
            : $this->buildHeaderMap($rows[0]);

        // Which columns the mapper could not find, and what the sheet actually
        // calls its columns. Both go back to the screen: a heading the aliases
        // do not know is the one import failure a librarian can fix without a
        // developer, and it is invisible from the counts alone.
        $missingColumns = $context['mode'] === 'st'
            ? []
            : $this->missingColumns($columnMap);

        $headers = $context['mode'] === 'st'
            ? []
            : $this->headerLabels($rows[0]);

        // Row -> image binding for this sheet. Bytes are read one row at a
        // time (see SheetPhotos), never all at once.
        $sheetPhotos = $this->photos->photosForSheet($path, $sheetName);

        // Skip the header row.
        $dataRows = array_slice($rows, 1);

        /** @var array<string, int> $issues outcome value => rows */
        $issues = [];

        foreach ($dataRows as $index => $row) {
            // dataRows[$index] = rows[$index + 1] (absolute index) — matches the image anchor.
            $photo = $sheetPhotos->get($index + 1);

            try {
                $result = $this->importRow($row, $columnMap, $context, $photo);
            } catch (\Throwable $e) {
                $rowNo = $index + 2; // 1-based + header
                Log::warning("readers:import — '{$sheetName}' {$rowNo}-qator xato: {$e->getMessage()}");
                $result = ReaderImportOutcome::SkippedError;
            }

            match ($result) {
                ReaderImportOutcome::Imported => $imported++,
                ReaderImportOutcome::Updated => $updated++,
                default => $skipped++,
            };

            if ($result->isSkipped()) {
                $issues[$result->value] = ($issues[$result->value] ?? 0) + 1;
            }

            if ($photo !== null && ! $result->isSkipped()) {
                $photoCount++;
            }
        }

        // Releases the archive handle SheetPhotos has been reading images from.
        $sheetPhotos->close();

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $this->sheetStat($context, $imported, $updated, $skipped, $photoCount, $issues, $missingColumns, $headers);
    }

    /**
     * @param  array{mode:string, type:?int, status:ReaderStatus}  $context
     * @param  array<string, int>  $issues
     * @param  array<int, string>  $missingColumns
     * @param  array<int, string>  $headers
     * @return SheetStat
     */
    private function sheetStat(
        array $context,
        int $imported,
        int $updated,
        int $skipped,
        int $photos,
        array $issues = [],
        array $missingColumns = [],
        array $headers = [],
    ): array {
        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'photos' => $photos,
            'type' => $this->typeName($context['type']),
            'issues' => $issues,
            'missing_columns' => $missingColumns,
            'headers' => $headers,
        ];
    }

    /**
     * Columns without which the sheet cannot produce a reader.
     *
     * `full_name` is mandatory outright. For the deduplication key either
     * `id_number` or `pinfl` will do, so it is only missing when neither was
     * recognised.
     *
     * @param  array<string, int>  $columnMap
     * @return array<int, string> human-readable names of what is missing
     */
    private function missingColumns(array $columnMap): array
    {
        $missing = [];

        if (! isset($columnMap['full_name'])) {
            $missing[] = __('F.I.Sh. (ism-familiya)');
        }

        if (! isset($columnMap['id_number']) && ! isset($columnMap['pinfl'])) {
            $missing[] = __('ID raqam yoki JSHSHIR');
        }

        return $missing;
    }

    /**
     * The sheet's own column headings, so the screen can show what it found
     * next to what it needed.
     *
     * @param  array<int, mixed>  $headerRow
     * @return array<int, string>
     */
    private function headerLabels(array $headerRow): array
    {
        $labels = [];

        foreach ($headerRow as $value) {
            $text = trim((string) $value);

            if ($text !== '') {
                $labels[] = $text;
            }
        }

        return $labels;
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
            'toliqism' => 'full_name',
            // "F.I.Sh." normalizes to "fish" once the dots are stripped. This
            // is the heading the real workbooks actually use, and its absence
            // meant full_name was never mapped — which skipped every single
            // row of a 563-row sheet while reporting no error at all, since a
            // row with no name is indistinguishable from a group header.
            'fish' => 'full_name',
            'fio' => 'full_name',
            'фио' => 'full_name',
            'familiyaismisharifi' => 'full_name',
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
            ['o‘', "o'", 'o`', 'g‘', "g'", 'g`', '‘', '’', '`', "'"],
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
     */
    private function importRow(array $row, array $columnMap, array $context, ?array $photo = null): ReaderImportOutcome
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

        // Skip group-header / empty rows. Which of the three it is decides
        // what the screen can tell the librarian afterwards: a wholly blank
        // row is Excel padding and expected, whereas a row carrying an ID but
        // no name almost always means the name column was not recognised.
        if (! $this->isPersonRow($fullName, $idNumber, $passport, $pinfl)) {
            $hasAnything = $fullName !== null || $idNumber !== null || $passport !== null || $pinfl !== null;

            if (! $hasAnything) {
                return ReaderImportOutcome::SkippedEmptyRow;
            }

            if ($fullName === null || $fullName === '') {
                return ReaderImportOutcome::SkippedNoName;
            }

            return ReaderImportOutcome::SkippedGroupHeader;
        }

        // A dedup key is required: id_number or pinfl.
        if ($idNumber === null && $pinfl === null) {
            return ReaderImportOutcome::SkippedNoIdentifier;
        }

        // Determine type: in Ketkenler from the ID prefix; otherwise the context type.
        $type = $context['type'];
        if ($context['mode'] === 'left') {
            $type = $this->typeFromIdNumber($idNumber);
            if ($type === null) {
                return ReaderImportOutcome::SkippedUnknownType; // prefix not found -> drop
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

        // Only carry id_number when the row actually has one: a sheet that
        // omits the column (ST) must not blank out an ID a previous import
        // established.
        if ($idNumber !== null) {
            $attributes['id_number'] = $idNumber;
        }

        $existing = $this->findExisting($idNumber, $pinfl);
        $isNew = $existing === null;

        $reader = $this->upsert($existing, $attributes);
        $key = $idNumber ?? (string) $pinfl;

        if ($photo !== null) {
            $this->attachPhoto($reader, $key, $photo);
        }

        return $isNew ? ReaderImportOutcome::Imported : ReaderImportOutcome::Updated;
    }

    /**
     * Finds the person this row already refers to, by either identifier.
     *
     * `id_number` is unique in the schema and `pinfl` is not, so matching on
     * id_number alone was enough to prevent duplicates only while every sheet
     * carried it. The ST sheet has no ID column: a reader created from ST (keyed
     * on PINFL) and then met again in a sheet that does have an ID would not be
     * found, and a second row for the same person would be created — with the
     * same PINFL, which the database does not forbid. Falling back to PINFL
     * merges the two instead.
     *
     * Order matters: id_number is the unique, authoritative key, so a match on
     * it wins. Only when it finds nothing does PINFL get a say.
     */
    private function findExisting(?string $idNumber, ?string $pinfl): ?Reader
    {
        if ($idNumber !== null) {
            $byIdNumber = Reader::query()->where('id_number', $idNumber)->first();

            if ($byIdNumber !== null) {
                return $byIdNumber;
            }
        }

        if ($pinfl !== null) {
            return Reader::query()->where('pinfl', $pinfl)->first();
        }

        return null;
    }

    /**
     * Creates the reader, or updates an existing one WITHOUT letting empty
     * cells erase what is already stored.
     *
     * An import used to write every field verbatim, nulls included, so a blank
     * cell in the workbook silently wiped a phone number or an address a
     * librarian had entered by hand. Re-importing the same file to pick up a
     * few fixed rows would undo that work across every row it touched, with
     * nothing on screen to say so.
     *
     * The workbook is therefore treated as authoritative for what it states,
     * and silent about what it leaves blank. Clearing a field stays a job for
     * the admin panel, where it is deliberate.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function upsert(?Reader $existing, array $attributes): Reader
    {
        if ($existing === null) {
            return Reader::create($attributes);
        }

        // Not array_filter()'s default callback: that would also drop "0",
        // an empty string and false, which are real values here.
        $stated = array_filter($attributes, static fn (mixed $value): bool => $value !== null);

        $existing->fill($stated)->save();

        return $existing;
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

        $path = 'readers/photos/'.$safe.'.'.$photo['ext'];

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
