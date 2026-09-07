<?php

namespace App\Services;

use App\Support\SheetPhotos;
use SimpleXMLElement;
use ZipArchive;

/**
 * Extracts images embedded in an Excel (.xlsx) sheet, keyed by ROW.
 *
 * An .xlsx is really a ZIP: images live in `xl/media/`, their row bindings
 * are in `xl/drawings/drawingN.xml` (anchor: <xdr:from><xdr:row>) and `.rels` (rId -> media).
 * We parse it directly, without loading heavy PhpSpreadsheet.
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
     * Row -> image binding for one sheet, as a lazy reader.
     *
     * Only the anchor map (a few KB of XML) is parsed here; the image bytes
     * themselves are read one at a time by SheetPhotos::get(), as each row is
     * imported. Returning every image at once — which this used to do — meant
     * a sheet with a thousand photos put a few hundred megabytes on the heap
     * before the first row was touched, and the host killed the PHP worker
     * for it.
     *
     * The archive handle is handed to the returned object, which owns it from
     * that point on; the caller must close() it when the sheet is done.
     */
    public function photosForSheet(string $path, string $sheetName): SheetPhotos
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return SheetPhotos::empty();
        }

        $sheetTarget = $this->resolveSheetTarget($zip, $sheetName);
        $drawingFile = $sheetTarget === null ? null : $this->resolveDrawingFile($zip, $sheetTarget);

        if ($drawingFile === null) {
            $zip->close();

            return SheetPhotos::empty();
        }

        $drawRaw = $zip->getFromName('xl/drawings/'.$drawingFile);
        $relsRaw = $zip->getFromName('xl/drawings/_rels/'.$drawingFile.'.rels');

        if ($drawRaw === false || $relsRaw === false) {
            $zip->close();

            return SheetPhotos::empty(); // empty drawing (no images)
        }

        // rId => media file name
        $media = [];
        foreach ((new SimpleXMLElement($relsRaw))->Relationship as $rel) {
            $media[(string) $rel['Id']] = basename((string) $rel['Target']);
        }

        // anchor: 0-based absolute row => media file name (NOT its bytes)
        $rowMedia = [];
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

                // Only raster images (png/jpg/gif) — drop emf/wmf and the like,
                // here rather than on read, so count() reflects what's importable.
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (! in_array($ext === 'jpeg' ? 'jpg' : $ext, ['png', 'jpg', 'gif'], true)) {
                    continue;
                }

                $rowMedia[$row] = $file;
            }
        }

        return new SheetPhotos($zip, $rowMedia);
    }

    /**
     * Sheet name -> `worksheets/sheetN.xml` path (via workbook.xml + rels).
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
     * `worksheets/sheetN.xml` -> its `drawingN.xml` file (via sheet rels).
     */
    private function resolveDrawingFile(ZipArchive $zip, string $sheetTarget): ?string
    {
        $base = basename($sheetTarget); // sheetN.xml
        $relRaw = $zip->getFromName('xl/worksheets/_rels/'.$base.'.rels');
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
