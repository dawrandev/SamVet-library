<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reader;
use App\Services\ReaderCardService;
use Symfony\Component\HttpFoundation\Response;

class ReaderCardController extends Controller
{
    public function __construct(
        private readonly ReaderCardService $cards,
    ) {}

    /** Outputs the reader's membership card (ID card) as a two-page PDF: outside, then inside. */
    public function show(Reader $reader): Response
    {
        // Opens in the browser (target=_blank) — the user views, prints, or saves it.
        return $this->cards->cardPdf($reader)
            ->stream('guvohnoma-'.($reader->id_number ?: $reader->id).'.pdf');
    }

    /** Outputs the reader's formulyar cover as a PDF. */
    public function famulyar(Reader $reader): Response
    {
        return $this->cards->formulyarPdf($reader)
            ->stream('formulyar-'.($reader->id_number ?: $reader->id).'.pdf');
    }
}
