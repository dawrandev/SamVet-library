<?php

namespace App\Http\Controllers\Concerns;

use App\Support\ByteRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a file from the private disk, honouring HTTP Range requests.
 *
 * The three readers (book/article/dissertation PDFs, video tracks, audio
 * tracks) had three near-identical copies of this, and they had drifted: the
 * media ones answered Range requests, the PDF one did not, so opening page one
 * of a 100 MB book read and sent all 100 MB. On the production host, whose
 * account is limited to 1 MB/s of disk I/O, that is a hundred seconds of a PHP
 * worker per open. PDF.js asks for ranges as soon as the server advertises
 * them, and then fetches only the pages actually being looked at.
 *
 * Range arithmetic lives in ByteRange, which is where the bounds are checked —
 * the copies here clamped only the end, so a start past the file produced a
 * negative Content-Length instead of a 416.
 *
 * Reads and flushes in manual chunks: fpassthru() does not reliably flush PHP's
 * own output buffer on this stack, so a large file would accumulate in memory
 * (a 600 MB PDF killed a 512 MB worker outright).
 */
trait StreamsPrivateFiles
{
    /**
     * @param  string  $mime  sent as-is unless $detectMime, in which case it is
     *                        the fallback. PDFs are pinned rather than sniffed:
     *                        the type is known from the route, and letting the
     *                        stored bytes choose the Content-Type is how an
     *                        upload ends up being served as something else.
     */
    protected function streamPrivateFile(
        Request $request,
        string $path,
        string $downloadName,
        string $mime,
        bool $detectMime = false,
    ): StreamedResponse {
        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), 404);

        $size = $disk->size($path);
        $range = ByteRange::parse($request->header('Range'), $size);

        // 416 rather than a clamped guess: a client asking past the end of the
        // file is working from stale information, and quietly handing it
        // different bytes hides that from both sides.
        abort_if($range === null, 416, 'Requested Range Not Satisfiable');

        if ($detectMime) {
            $mime = $disk->mimeType($path) ?: $mime;
        }

        $headers = [
            'Content-Type' => $mime,
            'Content-Length' => (string) $range->length(),
            // Advertised even on a full response: this is what tells the client
            // it may ask for ranges at all.
            'Accept-Ranges' => 'bytes',
            'Content-Disposition' => 'inline; filename="'.$downloadName.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($range->isPartial) {
            $headers['Content-Range'] = $range->contentRange($size);
        }

        return response()->stream(function () use ($disk, $path, $range): void {
            $stream = $disk->readStream($path);

            if ($range->start > 0) {
                fseek($stream, $range->start);
            }

            $remaining = $range->length();

            while ($remaining > 0 && ! feof($stream)) {
                $chunk = min(1024 * 1024, $remaining); // 1 MB chunks
                echo fread($stream, $chunk);
                flush();
                $remaining -= $chunk;
            }

            fclose($stream);
        }, $range->status(), $headers);
    }
}
