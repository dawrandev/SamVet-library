<?php

namespace App\Services;

use SimpleXMLElement;
use ZipArchive;

/**
 * Excel (.xlsx) varag'iga joylashtirilgan rasmlarni QATOR bo'yicha ajratib oladi.
 *
 * .xlsx aslida ZIP: rasmlar `xl/media/`da, ularning qatorга biriktirilishi
 * `xl/drawings/drawingN.xml` (anchor: <xdr:from><xdr:row>) va `.rels` (rId -> media) da.
 * PhpSpreadsheet'ni og'ir yuklamasdan, to'g'ridan-to'g'ri parslaymiz.
 *
 * @phpstan-type Photo array{bytes:string, ext:string}
 */
class ReaderPhotoExtractor
{
    private const XDR = 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing';
    private const A = 'http://schemas.openxmlformats.org/drawingml/2006/main';
    private const R = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const MAIN = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    /**
     * Varaqdagi rasmlar: 0-based ABSOLUT qator indeksi => rasm.
     * (toArray(null,true,false,false) bilan bir xil indeks — sarlavha = 0.)
     *
     * @return array<int, Photo>
     */
    public function photosForSheet(string $path, string $sheetName): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        try {
            $sheetTarget = $this->resolveSheetTarget($zip, $sheetName);
            if ($sheetTarget === null) {
                return [];
            }

            $drawingFile = $this->resolveDrawingFile($zip, $sheetTarget);
            if ($drawingFile === null) {
                return [];
            }

            $drawRaw = $zip->getFromName('xl/drawings/' . $drawingFile);
            $relsRaw = $zip->getFromName('xl/drawings/_rels/' . $drawingFile . '.rels');
            if ($drawRaw === false || $relsRaw === false) {
                return []; // bo'sh drawing (rasmsiz)
            }

            // rId => media fayl nomi
            $media = [];
            foreach ((new SimpleXMLElement($relsRaw))->Relationship as $rel) {
                $media[(string) $rel['Id']] = basename((string) $rel['Target']);
            }

            // anchor: qator => rId => media bayt
            $photos = [];
            $dx = new SimpleXMLElement($drawRaw);
            foreach (['oneCellAnchor', 'twoCellAnchor'] as $type) {
                foreach ($dx->children(self::XDR)->{$type} as $anchor) {
                    $pic = $anchor->children(self::XDR)->pic;
                    if (! $pic) {
                        continue;
                    }

                    $row = (int) $anchor->children(self::XDR)->from->children(self::XDR)->row;
                    $rid = (string) $pic->children(self::XDR)->blipFill
                        ->children(self::A)->blip->attributes(self::R)->embed;

                    $file = $media[$rid] ?? null;
                    if ($file === null) {
                        continue;
                    }

                    $bytes = $zip->getFromName('xl/media/' . $file);
                    if ($bytes === false || $bytes === '') {
                        continue;
                    }

                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    $ext = $ext === 'jpeg' ? 'jpg' : $ext;

                    // Faqat raster rasmlar (png/jpg/gif) — emf/wmf kabilarni tashlaymiz.
                    if (! in_array($ext, ['png', 'jpg', 'gif'], true)) {
                        continue;
                    }

                    $photos[$row] = ['bytes' => $bytes, 'ext' => $ext];
                }
            }

            return $photos;
        } finally {
            $zip->close();
        }
    }

    /**
     * Varaq nomi -> `worksheets/sheetN.xml` yo'li (workbook.xml + rels orqali).
     */
    private function resolveSheetTarget(ZipArchive $zip, string $sheetName): ?string
    {
        $wbRaw = $zip->getFromName('xl/workbook.xml');
        $relRaw = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($wbRaw === false || $relRaw === false) {
            return null;
        }

        // rId -> target (worksheets/sheetN.xml)
        $ridToTarget = [];
        foreach ((new SimpleXMLElement($relRaw))->Relationship as $rel) {
            $ridToTarget[(string) $rel['Id']] = (string) $rel['Target'];
        }

        $wb = new SimpleXMLElement($wbRaw);
        $wb->registerXPathNamespace('m', self::MAIN);

        $needle = trim($sheetName);
        foreach ($wb->xpath('//m:sheets/m:sheet') ?: [] as $sheet) {
            if (trim((string) $sheet['name']) !== $needle) {
                continue;
            }
            $rid = (string) $sheet->attributes(self::R)->id;

            return $ridToTarget[$rid] ?? null;
        }

        return null;
    }

    /**
     * `worksheets/sheetN.xml` -> uning `drawingN.xml` fayli (sheet rels orqali).
     */
    private function resolveDrawingFile(ZipArchive $zip, string $sheetTarget): ?string
    {
        $base = basename($sheetTarget); // sheetN.xml
        $relRaw = $zip->getFromName('xl/worksheets/_rels/' . $base . '.rels');
        if ($relRaw === false) {
            return null;
        }

        foreach ((new SimpleXMLElement($relRaw))->Relationship as $rel) {
            if (str_contains((string) $rel['Target'], 'drawing')) {
                return basename((string) $rel['Target']);
            }
        }

        return null;
    }
}
