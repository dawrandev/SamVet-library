<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\AudioReaderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Protected online listening. Audio files live on the private disk and are
 * never linked directly: they're streamed through these auth-guarded
 * actions, inline only, with HTTP range support so the <audio> element can
 * seek (unlike OnlineReaderController's PDF stream(), which is read linearly
 * start-to-end since PDF.js doesn't need byte-range seeking).
 */
class AudioReaderController extends Controller
{
    public function __construct(
        private readonly AudioReaderService $reader,
    ) {}

    public function show(string $slug): View
    {
        $audiobook = $this->reader->audiobook($slug);

        return view('pages.site.audio-player', [
            'audiobook' => $audiobook,
            'backUrl' => route('audiobook.show', $audiobook->slug),
        ]);
    }

    public function trackFile(Request $request, string $slug, int $track): StreamedResponse
    {
        $audiobook = $this->reader->audiobook($slug);
        $track = $this->reader->track($audiobook, $track);

        return $this->stream($request, $track->audio_file);
    }

    /**
     * Streams a private audio file, honoring HTTP Range requests (206 Partial
     * Content) so the player can seek without downloading the whole file
     * first. Reads/flushes in manual chunks — same rationale as the PDF
     * reader's stream(): fpassthru() doesn't reliably flush PHP's own output
     * buffer on this stack, so a large file would silently accumulate in memory.
     */
    private function stream(Request $request, string $path): StreamedResponse
    {
        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), 404);

        $size = $disk->size($path);
        $mime = $disk->mimeType($path) ?: 'audio/mpeg';

        $start = 0;
        $end = $size - 1;
        $status = 200;

        $range = $request->header('Range');
        if ($range && preg_match('/bytes=(\d*)-(\d*)/', $range, $matches)) {
            $start = $matches[1] === '' ? 0 : (int) $matches[1];
            $end = $matches[2] === '' ? $size - 1 : min((int) $matches[2], $size - 1);
            $status = 206;
        }

        $length = $end - $start + 1;

        $headers = [
            'Content-Type' => $mime,
            'Content-Length' => $length,
            'Accept-Ranges' => 'bytes',
            'Content-Disposition' => 'inline; filename="audio"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($status === 206) {
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
        }

        return response()->stream(function () use ($disk, $path, $start, $length) {
            $stream = $disk->readStream($path);
            fseek($stream, $start);

            $remaining = $length;
            while ($remaining > 0 && ! feof($stream)) {
                $chunk = min(1024 * 1024, $remaining); // 1 MB chunks
                echo fread($stream, $chunk);
                flush();
                $remaining -= $chunk;
            }
            fclose($stream);
        }, $status, $headers);
    }
}
