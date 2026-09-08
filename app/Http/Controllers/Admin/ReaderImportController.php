<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReaderImportOutcome;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportReadersRequest;
use App\Services\ReaderImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ReaderImportController extends Controller
{
    public function __construct(
        private readonly ReaderImportService $importService,
    ) {}

    /** Displays the import page (file upload form). */
    public function create(): View
    {
        return view('pages.admin.readers.import');
    }

    /** Imports the uploaded Excel file. */
    public function store(ImportReadersRequest $request): RedirectResponse
    {
        // Remove the time limit for large files (thousands of rows + images).
        set_time_limit(0);

        // The file is NOT public — it is stored temporarily under storage/app/imports.
        $path = $request->file('file')->store('imports');
        $fullPath = Storage::path($path);

        try {
            $stats = $this->importService->import($fullPath);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('import_error', __('Import xatosi: :msg', ['msg' => $e->getMessage()]));
        } finally {
            Storage::delete($path); // clean up the temporary file
        }

        return back()->with('import_stats', $this->summarize($stats));
    }

    /**
     * Aggregates the statistics returned by the service for the page.
     *
     * Alongside the counts it builds a per-sheet breakdown of why rows were
     * skipped. A bare "563 o'tkazildi" is what a real import produced when the
     * name column was headed "F.I.Sh." and the alias table did not know that
     * spelling: correct, and useless. `needs_attention` separates a file the
     * librarian can fix from the trailing blank rows every Excel sheet has.
     *
     * @param  array<string, array<string, mixed>>  $stats
     * @return array<string, mixed>
     */
    private function summarize(array $stats): array
    {
        $sheets = [];
        $problems = [];
        $needsAttention = false;
        $total = ['imported' => 0, 'updated' => 0, 'skipped' => 0, 'photos' => 0];

        foreach ($stats as $sheet => $stat) {
            $sheets[] = [
                'sheet' => $sheet,
                'type' => $stat['type'] ?? '—',
                'imported' => $stat['imported'],
                'updated' => $stat['updated'],
                'skipped' => $stat['skipped'],
                'photos' => $stat['photos'] ?? 0,
            ];
            $total['imported'] += $stat['imported'];
            $total['updated'] += $stat['updated'];
            $total['skipped'] += $stat['skipped'];
            $total['photos'] += $stat['photos'] ?? 0;

            $problem = $this->sheetProblem($sheet, $stat);

            if ($problem !== null) {
                $problems[] = $problem;
                $needsAttention = $needsAttention || $problem['needs_attention'];
            }
        }

        return [
            'sheets' => $sheets,
            'total' => $total,
            'problems' => $problems,
            'needs_attention' => $needsAttention,
        ];
    }

    /**
     * One sheet's explanation, or null when it has nothing to explain.
     *
     * @param  array<string, mixed>  $stat
     * @return array<string, mixed>|null
     */
    private function sheetProblem(string $sheet, array $stat): ?array
    {
        $missingColumns = $stat['missing_columns'] ?? [];
        $error = $stat['error'] ?? null;
        $issues = [];
        $attention = $error !== null || $missingColumns !== [];

        foreach ($stat['issues'] ?? [] as $value => $count) {
            $outcome = ReaderImportOutcome::tryFrom($value);

            if ($outcome === null) {
                continue;
            }

            $issues[] = [
                'label' => $outcome->label(),
                'count' => $count,
                'attention' => $outcome->needsAttention(),
            ];

            $attention = $attention || $outcome->needsAttention();
        }

        if ($issues === [] && $missingColumns === [] && $error === null) {
            return null;
        }

        // Loudest first: what the librarian should read is the reason that can
        // be acted on, not whichever bucket happens to be biggest.
        usort($issues, static fn (array $a, array $b): int => [$b['attention'], $b['count']] <=> [$a['attention'], $a['count']]);

        return [
            'sheet' => $sheet,
            'error' => $error,
            'missing_columns' => $missingColumns,
            'headers' => $stat['headers'] ?? [],
            'issues' => $issues,
            'needs_attention' => $attention,
        ];
    }
}
