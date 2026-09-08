<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Concerns\StreamsPrivateFiles;
use App\Http\Controllers\Controller;
use App\Services\OnlineReadService;
use App\Services\Site\AudioReaderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    use StreamsPrivateFiles;

    public function __construct(
        private readonly AudioReaderService $reader,
        private readonly OnlineReadService $onlineReads,
    ) {}

    public function show(string $slug): View
    {
        $audiobook = $this->reader->audiobook($slug);

        $this->onlineReads->log(Auth::guard('reader')->user(), $audiobook);

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
     * Streams a private audio file. Range handling, bounds checking and the
     * chunked read all live in the shared trait — this used to be a third copy
     * of them, and the copies had already drifted apart.
     */
    private function stream(Request $request, string $path): StreamedResponse
    {
        return $this->streamPrivateFile($request, $path, 'audio', 'audio/mpeg', detectMime: true);
    }
}
