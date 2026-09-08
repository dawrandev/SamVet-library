<?php

namespace App\Support;

/**
 * A parsed HTTP `Range` header (RFC 9110 §14), resolved against a known file
 * size.
 *
 * This exists because the header is attacker-controlled arithmetic that then
 * decides how many bytes a stream reads. The video reader used to compute it
 * inline as `min($end, $size - 1)` with no lower bound at all, so
 * `Range: bytes=999999999-` on a 10 MB file produced a negative length and a
 * malformed response instead of the 416 the spec asks for. Doing it in one
 * audited place is what keeps that from being re-derived, differently, in each
 * of the three readers.
 *
 * Parsing is deliberately conservative — anything not understood is served as
 * the whole file, which is always a valid answer.
 */
final readonly class ByteRange
{
    private function __construct(
        public int $start,
        public int $end,
        /** False when the whole file is being served (status 200, no Content-Range). */
        public bool $isPartial,
    ) {}

    public function length(): int
    {
        return $this->end - $this->start + 1;
    }

    public function status(): int
    {
        return $this->isPartial ? 206 : 200;
    }

    /** `bytes <start>-<end>/<size>`, for the Content-Range header. */
    public function contentRange(int $size): string
    {
        return "bytes {$this->start}-{$this->end}/{$size}";
    }

    /**
     * @param  string|null  $header  the raw Range header, if any
     * @param  int  $size  the file's real size in bytes
     * @return self|null null means the request cannot be satisfied — the
     *                   caller must answer 416, not clamp it into something
     *                   plausible: a client asking past the end of a file is
     *                   working from wrong information and silently handing
     *                   it different bytes hides that.
     */
    public static function parse(?string $header, int $size): ?self
    {
        $whole = new self(0, max(0, $size - 1), false);

        if ($header === null || trim($header) === '' || $size <= 0) {
            return $whole;
        }

        // Only the "bytes" unit exists in practice; an unknown unit must be
        // ignored rather than guessed at.
        if (! preg_match('/^\s*bytes\s*=\s*(.+)$/i', $header, $matches)) {
            return $whole;
        }

        $spec = trim($matches[1]);

        // Multiple ranges are legal and would require a multipart/byteranges
        // body. Nothing this application serves asks for them — PDF.js and
        // media players send one range at a time — so the whole file is the
        // honest answer rather than a response that claims to be one range
        // while ignoring the rest.
        if (str_contains($spec, ',')) {
            return $whole;
        }

        // "-500" — the last 500 bytes.
        if (preg_match('/^-(\d+)$/', $spec, $suffix)) {
            $wanted = (int) $suffix[1];

            if ($wanted === 0) {
                return null;
            }

            return new self(max(0, $size - $wanted), $size - 1, true);
        }

        if (! preg_match('/^(\d+)-(\d*)$/', $spec, $bounds)) {
            return $whole;
        }

        $start = (int) $bounds[1];
        $end = $bounds[2] === '' ? $size - 1 : (int) $bounds[2];

        // Past the end of the file, or inverted: unsatisfiable.
        if ($start >= $size || $start > $end) {
            return null;
        }

        return new self($start, min($end, $size - 1), true);
    }
}
