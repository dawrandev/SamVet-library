<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Concerns\StreamsPrivateFiles;
use App\Http\Controllers\Controller;
use App\Services\OnlineReadService;
use App\Services\Site\VideoReaderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Protected online watching. Video files live on the private disk and are
 * never linked directly: they're streamed through these auth-guarded
 * actions, inline only, with HTTP range support so the <video> element can
 * seek — same rationale as AudioReaderController.
 */
class VideoReaderController extends Controller
{
    use StreamsPrivateFiles;

    public function __construct(
        private readonly VideoReaderService $reader,
        private readonly OnlineReadService $onlineReads,
    ) {}

    public function show(string $slug): View
    {
        $video = $this->reader->video($slug);

        $this->onlineReads->log(Auth::guard('reader')->user(), $video);

        return view('pages.site.video-player', [
            'video' => $video,
            'backUrl' => route('video.show', $video->slug),
        ]);
    }

    public function trackFile(Request $request, string $slug, int $track): StreamedResponse
    {
        $video = $this->reader->video($slug);
        $track = $this->reader->track($video, $track);

        return $this->stream($request, $track->video_file);
    }

    /**
     * Streams a private video file. Range handling, bounds checking and the
     * chunked read all live in the shared trait — this used to be a third copy
     * of them, and the copies had already drifted apart.
     */
    private function stream(Request $request, string $path): StreamedResponse
    {
        return $this->streamPrivateFile($request, $path, 'video', 'video/mp4', detectMime: true);
    }
}
