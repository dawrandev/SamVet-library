<?php

namespace App\Console\Commands;

use App\Services\ReaderImportService;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use ReflectionMethod;

/**
 * Explains why rows in a reader-import workbook were skipped.
 *
 * The import reports counts only, so "563 skipped" gives a librarian nothing to
 * act on: a header the mapper did not recognise, an ID format the person-check
 * rejects, and a sheet of genuine group headers all look identical from the
 * outside. This reproduces the decision for each row and names the branch that
 * dropped it.
 *
 * Read-only: it never writes a reader, and it reaches the real logic through
 * reflection on ReaderImportService rather than reimplementing it — a diagnosis
 * that drifts from the code it explains is worse than none.
 *
 * Personal data is masked in the output (first two characters plus a length),
 * because this is run to be pasted into a chat or a ticket.
 */
class ExplainReaderImport extends Command
{
    protected $signature = 'readers:explain
        {path : .xlsx fayl yo\'li}
        {--sheet= : Faqat shu varaq (masalan BT)}
        {--rows=15 : Nechta qator batafsil ko\'rsatilsin}';

    protected $description = 'Kitobxonlar import faylida qatorlar nega o\'tkazib yuborilganini tushuntiradi';

    public function handle(ReaderImportService $service): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("Fayl topilmadi: {$path}");

            return self::FAILURE;
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        $sheetNames = $reader->listWorksheetNames($path);
        $this->info('Varaqlar: '.implode(', ', $sheetNames));

        $only = $this->option('sheet');

        foreach ($sheetNames as $sheetName) {
            if ($only !== null && strcasecmp($only, $sheetName) !== 0) {
                continue;
            }

            $this->explainSheet($service, $path, $sheetName);
        }

        return self::SUCCESS;
    }

    private function explainSheet(ReaderImportService $service, string $path, string $sheetName): void
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly([$sheetName]);

        $rows = $reader->load($path)->getActiveSheet()->toArray(null, true, false, false);

        $this->newLine();
        $this->line(str_repeat('=', 60));
        $this->line("VARAQ: {$sheetName} — ".count($rows).' qator');
        $this->line(str_repeat('=', 60));

        if ($rows === []) {
            $this->warn('Varaq bo\'sh.');

            return;
        }

        // The mapper is fed rows[0] by the importer, so that is the row whose
        // contents decide whether anything can be found at all.
        $this->line('1-qator (sarlavha deb qabul qilinadi):');

        foreach ($rows[0] as $index => $value) {
            $text = trim((string) $value);

            if ($text !== '') {
                $this->line(sprintf('  [%2d] %s', $index, $text));
            }
        }

        $buildHeaderMap = new ReflectionMethod($service, 'buildHeaderMap');
        $map = $buildHeaderMap->invoke($service, $rows[0]);

        $this->newLine();

        if ($map === []) {
            $this->error('SARLAVHA XARITASI BO\'SH — hech bir ustun tanilmadi.');
            $this->line('Sabab: 1-qator sarlavha emas (masalan sarlavha 2- yoki 3-qatorda),');
            $this->line('yoki ustun nomlari kutilganidan boshqacha yozilgan.');
        } else {
            $this->info('Tanilgan ustunlar: '.json_encode($map, JSON_UNESCAPED_UNICODE));

            foreach (['full_name', 'id_number', 'pinfl', 'passport'] as $needed) {
                if (! isset($map[$needed])) {
                    $this->warn("  yo'q: {$needed}");
                }
            }
        }

        $this->diagnoseRows($service, $rows, $map);
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<string, int>  $map
     */
    private function diagnoseRows(ReaderImportService $service, array $rows, array $map): void
    {
        $cleanString = new ReflectionMethod($service, 'cleanString');
        $cleanPinfl = new ReflectionMethod($service, 'cleanPinfl');
        $isPersonRow = new ReflectionMethod($service, 'isPersonRow');

        $get = function (array $row, string $field) use ($map, $cleanString, $service): ?string {
            if (! isset($map[$field])) {
                return null;
            }

            return $cleanString->invoke($service, $row[$map[$field]] ?? null);
        };

        $reasons = [];
        $samples = [];
        $limit = (int) $this->option('rows');

        $this->newLine();
        $this->line('Qatorlar tahlili (har bir sabab uchun namunalar):');

        foreach (array_slice($rows, 1) as $offset => $row) {
            $fullName = $get($row, 'full_name');
            $idNumber = $get($row, 'id_number');
            $passport = $get($row, 'passport');
            $pinfl = $cleanPinfl->invoke($service, $get($row, 'pinfl'));

            if (! $isPersonRow->invoke($service, $fullName, $idNumber, $passport, $pinfl)) {
                $verdict = $fullName === null || $fullName === ''
                    ? "O'TKAZILDI: ism ustuni bo'sh/topilmadi"
                    : "O'TKAZILDI: ID/JSHSHIR/pasport formatga mos emas";
            } elseif ($idNumber === null && $pinfl === null) {
                $verdict = "O'TKAZILDI: na ID raqami, na JSHSHIR bor";
            } else {
                $verdict = 'OK — saqlanadi';
            }

            $reasons[$verdict] = ($reasons[$verdict] ?? 0) + 1;

            // Samples are kept per verdict, not per file: the first rows of a
            // sheet are usually the healthy ones, so a flat "first N" listing
            // shows nothing about the rows that were actually dropped.
            if (count($samples[$verdict] ?? []) < $limit) {
                $samples[$verdict][] = sprintf(
                    '  %d-qator: ism=%s id=%s jshshr=%s pasport=%s',
                    $offset + 2,
                    $this->mask($fullName),
                    $this->mask($idNumber),
                    $this->mask($pinfl),
                    $this->mask($passport)
                );
            }
        }

        foreach ($samples as $verdict => $lines) {
            $this->newLine();
            $this->line("[{$verdict}] — {$reasons[$verdict]} ta");

            foreach ($lines as $line) {
                $this->line($line);
            }
        }

        $this->newLine();
        $this->line('XULOSA:');

        foreach ($reasons as $reason => $count) {
            $this->line(sprintf('  %-46s %d', $reason, $count));
        }
    }

    /** Shows enough to recognise a format without printing anyone's data. */
    private function mask(?string $value): string
    {
        if ($value === null || $value === '') {
            return '(bo\'sh)';
        }

        return mb_substr($value, 0, 2).'…('.mb_strlen($value).')';
    }
}
