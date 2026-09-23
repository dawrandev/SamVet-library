<?php

namespace App\Services;

use App\Data\ReaderCardData;
use App\Models\Reader;
use App\Support\ReaderCard\CardPalette;
use App\Support\ReaderCard\TextFitter;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Illuminate\Support\Facades\Storage;

/**
 * Builds the printed reader card (kitobxon guvohnomasi) and the formulyar
 * cover, both laid out from the Figma file "Savrina's ID".
 *
 * Every panel in that file is 1486 x 1050 px. Instead of converting each
 * measurement to points, the PDF's DPI is chosen so the page itself is that
 * size — 127 dpi on A4, 180 dpi on A5 — and the templates use the design's
 * numbers directly. Both give a ~1485 x 1049 px canvas.
 */
class ReaderCardService
{
    public const CARD_DPI = 127;

    public const FORMULYAR_DPI = 180;

    public function __construct(
        private readonly TextFitter $fitter,
    ) {}

    public function cardPdf(Reader $reader): PdfDocument
    {
        return Pdf::setOption($this->options(self::CARD_DPI))
            ->loadView('pages.admin.readers.card', ['card' => $this->data($reader)])
            ->setPaper('a4', 'landscape');
    }

    public function formulyarPdf(Reader $reader): PdfDocument
    {
        return Pdf::setOption($this->options(self::FORMULYAR_DPI))
            ->loadView('pages.admin.readers.famulyar', ['card' => $this->data($reader)])
            ->setPaper('a5', 'landscape');
    }

    public function data(Reader $reader): ReaderCardData
    {
        $reader->loadMissing(['type', 'affiliationPlace', 'affiliationUnit', 'affiliationGroup']);

        // "Familyasi Ismi Sharifi" from the one stored full_name — there are no
        // separate columns. First word surname, second first name, the rest the
        // patronymic ("Xabibulla o‘g‘li" stays whole), the standard Uzbek order.
        $parts = preg_split('/\s+/', trim((string) $reader->full_name), 3) ?: [];
        [$surname, $firstName, $patronymic] = array_pad($parts, 3, '');

        $idNumber = (string) ($reader->id_number ?: '—');
        $medium = resource_path('fonts/Inter-Medium.ttf');
        $mono = resource_path('fonts/RobotoMono-Medium.ttf');

        return new ReaderCardData(
            reader: $reader,
            palette: CardPalette::fromAccent($reader->type?->certificate_color),
            fontDir: $this->cssPath(resource_path('fonts')),
            logoPath: $this->cssPath(resource_path('images/reader-card/logo.png')),
            stencilPath: $this->cssPath(resource_path('images/reader-card/pattern-stencil.png')),
            photoPath: $this->photoPath($reader),
            surname: $surname,
            firstName: $firstName,
            patronymic: $patronymic,
            idNumber: $idNumber,
            sizes: [
                // Name fields: 298px column, 30px in the design, never below 18.
                'surname' => $this->fitter->fit($surname, $medium, 298, 30, 18, 0.06),
                'firstName' => $this->fitter->fit($firstName, $medium, 298, 30, 18, 0.06),
                'patronymic' => $this->fitter->fit($patronymic, $medium, 298, 30, 18, 0.06),
                // Place of study/work: full width of the grey box, minus padding.
                'place' => $this->fitter->fit((string) $reader->affiliationPlace?->name, $medium, 600, 30, 18, 0.06),
                // ID badges. The card's is Roboto Mono (slashed zeros); the
                // formulyar's two are Inter Bold, widely spaced — different in Figma too.
                'cardId' => $this->fitter->fit($idNumber, $mono, 292, 48, 24, 0.06),
                'formulyarId' => $this->fitter->fit($idNumber, resource_path('fonts/Inter-Bold.ttf'), 470, 64, 32, 0.18),
            ],
        );
    }

    /** @return array<string, mixed> */
    private function options(int $dpi): array
    {
        return [
            'dpi' => $dpi,
            // Four Inter weights plus Roboto Mono would otherwise go into every
            // PDF whole, ~1.7 MB of font for a one-page card.
            'isFontSubsettingEnabled' => true,
        ];
    }

    /**
     * The reader's uploaded photo as a local path. A data: URI was tried first
     * but dompdf routes it through the same gate as remote URLs, disabled by
     * default (enable_remote=false), and the image silently failed to load. A
     * local path works: storage/app/public is inside dompdf's chroot.
     */
    private function photoPath(Reader $reader): ?string
    {
        if (! $reader->photo) {
            return null;
        }

        $path = Storage::disk('public')->path($reader->photo);

        return is_file($path) ? $this->cssPath($path) : null;
    }

    /**
     * Forward slashes on every OS: these paths go inside CSS url(), where a
     * Windows backslash reads as an escape and the file silently fails to load.
     */
    private function cssPath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
