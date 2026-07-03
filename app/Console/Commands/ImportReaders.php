<?php

namespace App\Console\Commands;

use App\Services\ReaderImportService;
use Illuminate\Console\Command;

/**
 * Katta ko'p varaqli Excel'dan kutubxona a'zolarini (readers) import qiladi.
 * Biznes logika ReaderImportService'da — buyruq yupqa.
 */
class ImportReaders extends Command
{
    protected $signature = 'readers:import {path? : Excel fayl yo\'li}';

    protected $description = 'Excel (ko\'p varaqli) fayldan kutubxona a\'zolarini import qilish';

    private const DEFAULT_PATH = '_import/Kitobxon guvohnomasi ID raqam.xlsx';

    public function handle(ReaderImportService $service): int
    {
        $path = $this->argument('path') ?? base_path(self::DEFAULT_PATH);

        if (! is_file($path)) {
            $this->error("Fayl topilmadi: {$path}");

            return self::FAILURE;
        }

        $this->info("Import boshlandi: {$path}");
        $this->newLine();

        $service->onSheet(function (string $sheet, string $message): void {
            $this->line("  [{$sheet}] {$message}");
        });

        $stats = $service->import($path);

        $this->newLine();
        $this->renderSummary($stats);

        return self::SUCCESS;
    }

    /**
     * @param  array<string, array{imported:int, updated:int, skipped:int, photos:int, type:?string}>  $stats
     */
    private function renderSummary(array $stats): void
    {
        $rows = [];
        $totalImported = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;
        $totalPhotos = 0;

        foreach ($stats as $sheet => $stat) {
            $rows[] = [
                $sheet,
                $stat['type'] ?? '—',
                $stat['imported'],
                $stat['updated'],
                $stat['skipped'],
                $stat['photos'] ?? 0,
            ];
            $totalImported += $stat['imported'];
            $totalUpdated += $stat['updated'];
            $totalSkipped += $stat['skipped'];
            $totalPhotos += $stat['photos'] ?? 0;
        }

        $rows[] = ['—', '—', '—', '—', '—', '—'];
        $rows[] = ['JAMI', '', $totalImported, $totalUpdated, $totalSkipped, $totalPhotos];

        $this->table(
            ['Varaq', 'Type', 'Yangi', 'Yangilandi', 'Skip', 'Rasm'],
            $rows,
        );
    }
}
