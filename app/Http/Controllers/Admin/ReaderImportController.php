<?php

namespace App\Http\Controllers\Admin;

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
     * @param  array<string, array{imported:int, updated:int, skipped:int, photos:int, type:?string}>  $stats
     * @return array{sheets: array<int, array{sheet:string, type:string, imported:int, updated:int, skipped:int, photos:int}>, total: array{imported:int, updated:int, skipped:int, photos:int}}
     */
    private function summarize(array $stats): array
    {
        $sheets = [];
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
        }

        return ['sheets' => $sheets, 'total' => $total];
    }
}
