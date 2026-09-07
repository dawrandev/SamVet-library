<?php

namespace App\Support;

use ZipArchive;

/**
 * Lazy access to the images embedded in one Excel sheet, keyed by row.
 *
 * The point of this class is what it does NOT hold: image bytes. Only the
 * row -> filename map is built up front (parsed from the drawing XML, a few
 * KB), and each image is read out of the still-open archive at the moment its
 * row is imported, so at most one photo is in memory at a time.
 *
 * The previous version returned every image of the sheet as a single array of
 * raw byte strings. For a real reader list — well over a thousand rows, each
 * with a photo — that meant holding hundreds of megabytes before the first row
 * was even processed, which is what pushed the import past the host's memory
 * ceiling and got the PHP worker killed mid-request (a raw 503 from Apache,
 * with nothing in Laravel's log because the process never got to write one).
 *
 * Callers MUST close() when finished — the archive handle stays open for the
 * lifetime of the import of that sheet.
 *
 * @phpstan-type Photo array{bytes:string, ext:string}
 */
class SheetPhotos
{
    /** Raster formats worth importing — emf/wmf and friends are dropped. */
    private const IMAGE_EXTENSIONS = ['png', 'jpg', 'gif'];

    /**
     * @param  array<int, string>  $rowMedia  0-based absolute row index => `xl/media/...` entry name
     */
    public function __construct(
        private ?ZipArchive $zip,
        private readonly array $rowMedia = [],
    ) {}

    /** An instance holding nothing — used when the sheet has no drawing at all. */
    public static function empty(): self
    {
        return new self(null);
    }

    /**
     * Reads the image anchored to $row, or null when there isn't one (or it
     * is a format we don't import). Bytes are fetched here and nowhere else.
     *
     * @return Photo|null
     */
    public function get(int $row): ?array
    {
        $file = $this->rowMedia[$row] ?? null;

        if ($file === null || $this->zip === null) {
            return null;
        }

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;

        if (! in_array($ext, self::IMAGE_EXTENSIONS, true)) {
            return null;
        }

        $bytes = $this->zip->getFromName('xl/media/'.$file);

        if ($bytes === false || $bytes === '') {
            return null;
        }

        return ['bytes' => $bytes, 'ext' => $ext];
    }

    /** How many rows in this sheet have an image anchored to them. */
    public function count(): int
    {
        return count($this->rowMedia);
    }

    public function close(): void
    {
        $this->zip?->close();
        $this->zip = null;
    }
}
