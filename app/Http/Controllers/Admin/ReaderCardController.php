<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reader;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ReaderCardController extends Controller
{
    /** Outputs the reader's membership card (ID card) as a PDF. */
    public function show(Reader $reader): Response
    {
        $pdf = Pdf::loadView('pages.admin.readers.card', [
            'reader' => $reader,
            'photo' => $this->photoPath($reader),
        ])->setPaper('a4', 'landscape');

        // Opens in the browser (target=_blank) — the user views, prints, or saves it.
        return $pdf->stream('guvohnoma-'.($reader->id_number ?: $reader->id).'.pdf');
    }

    /** Outputs the famulyar (practicum booklet) cover page as a PDF. */
    public function famulyar(Reader $reader): Response
    {
        $pdf = Pdf::loadView('pages.admin.readers.famulyar', [
            'reader' => $reader,
            'logo' => $this->logoPath(),
        ])->setPaper('a5', 'landscape');

        return $pdf->stream('famulyar-'.($reader->id_number ?: $reader->id).'.pdf');
    }

    /**
     * The reader's uploaded photo's local path. A data: URI was tried first but
     * dompdf's image loader routes it through the same gate as remote URLs,
     * which is disabled by default (enable_remote=false) — the image simply
     * failed to load. A plain local path works instead, since storage/app/public
     * is inside dompdf's default chroot (base_path()).
     */
    private function photoPath(Reader $reader): ?string
    {
        if (! $reader->photo) {
            return null;
        }

        $path = Storage::disk('public')->path($reader->photo);

        return is_file($path) ? $path : null;
    }

    /**
     * The university logo's local path — the white-background flattened copy
     * (logo-print.png), not the transparent original: dompdf's GD-based PNG
     * handling renders alpha transparency as solid black, so the transparent
     * original looked broken here.
     */
    private function logoPath(): ?string
    {
        $path = public_path('images/samvet/logo-print.png');

        return is_file($path) ? $path : null;
    }
}
