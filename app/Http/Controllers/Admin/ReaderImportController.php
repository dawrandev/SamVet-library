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

    /** Import sahifasini (fayl yuklash formasi) ko'rsatadi. */
    public function create(): View
    {
        return view('pages.admin.readers.import');
    }

    /** Yuklangan Excel faylni import qiladi. */
    public function store(ImportReadersRequest $request): RedirectResponse
    {
        // Katta fayl (minglab qator + rasmlar) uchun vaqt limitini olib tashlaymiz.
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

        return back()->with('import_stats', $this->summarize($stats));
    }

    /**
     * Xizmat qaytargan statistikани sahifа uchun yig'ib beradi.
     *
     * @param  array<string, array{imported:int, updated:int, skipped:int, type:string}>  $stats
     * @return array{sheets: array<int, array{sheet:string, type:string, imported:int, updated:int, skipped:int}>, total: array{imported:int, updated:int, skipped:int}}
     */
    private function summarize(array $stats): array
    {
        $sheets = [];
        $total = ['imported' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($stats as $sheet => $stat) {
            $sheets[] = [
                'sheet' => $sheet,
                'type' => $stat['type'] ?? '—',
                'imported' => $stat['imported'],
                'updated' => $stat['updated'],
                'skipped' => $stat['skipped'],
            ];
            $total['imported'] += $stat['imported'];
            $total['updated'] += $stat['updated'];
            $total['skipped'] += $stat['skipped'];
        }

        return ['sheets' => $sheets, 'total' => $total];
    }
}
