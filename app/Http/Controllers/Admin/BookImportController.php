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

    /** Import sahifasini (fayl yuklash formasi) ko'rsatadi. */
    public function create(): View
    {
        return view('pages.admin.books.import');
    }

    /** Yuklangan Excel faylni import qiladi. */
    public function store(ImportBooksRequest $request): RedirectResponse
    {
        // Katta fayl uchun vaqt limitini olib tashlaymiz.
        set_time_limit(0);

        // Fayl public EMAS — storage/app/imports ostiga vaqtincha saqlanadi.
        $path = $request->file('file')->store('imports');
        $fullPath = Storage::path($path);

        try {
            $stats = $this->importService->import($fullPath);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('import_error', __('Import xatosi: :msg', ['msg' => $e->getMessage()]));
        } finally {
            Storage::delete($path); // vaqtinchalik faylni tozalash
        }

        return back()->with('import_stats', $stats);
    }
}
