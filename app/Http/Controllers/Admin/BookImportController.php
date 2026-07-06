<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportBooksRequest;
use App\Services\BookImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BookImportController extends Controller
{
    public function __construct(
        private readonly BookImportService $importService,
    ) {}

    /** Displays the import page (file upload form). */
    public function create(): View
    {
        return view('pages.admin.books.import');
    }

    /** Imports the uploaded Excel file. */
    public function store(ImportBooksRequest $request): RedirectResponse
    {
        // Remove the time limit for large files.
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

        return back()->with('import_stats', $stats);
    }
}
