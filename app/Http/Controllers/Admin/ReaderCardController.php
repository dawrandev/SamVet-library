<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reader;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ReaderCardController extends Controller
{
    /** Kitobxon guvohnomasini (ID-karta) PDF sifatida chiqaradi. */
    public function show(Reader $reader): Response
    {
        $pdf = Pdf::loadView('pages.admin.readers.card', [
            'reader' => $reader,
            'photo' => $this->photoDataUri($reader),
        ])->setPaper('a5', 'portrait');

        // Brauzerда ochiladi (target=_blank) — foydalanuvchi ko'radi, chop etadi yoki saqlaydi.
        return $pdf->stream('guvohnoma-'.($reader->id_number ?: $reader->id).'.pdf');
    }

    /**
     * Rasmni dompdf uchun base64 data-URI ga aylantiradi (fayl yo'li muammosini oldini oladi).
     * Rasm bo'lmasa null qaytaradi.
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
