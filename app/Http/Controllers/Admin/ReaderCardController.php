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
            'photo' => $this->photoDataUri($reader),
        ])->setPaper('a5', 'landscape');

        // Opens in the browser (target=_blank) — the user views, prints, or saves it.
        return $pdf->stream('guvohnoma-'.($reader->id_number ?: $reader->id).'.pdf');
    }

    /**
     * Converts the image into a base64 data URI for dompdf (avoids file path issues).
     * Returns null if there is no image.
     */
    private function photoDataUri(Reader $reader): ?string
    {
        if (! $reader->photo) {
            return null;
        }

        $path = Storage::disk('public')->path($reader->photo);

        if (! is_file($path)) {
            return null;
        }

        return 'data:'.mime_content_type($path).';base64,'.base64_encode((string) file_get_contents($path));
    }
}
