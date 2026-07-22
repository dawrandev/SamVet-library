<?php

namespace App\Services\Site;

use App\Models\Video;
use App\Models\VideoTrack;
use App\Repositories\Contracts\VideoRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Resolves the video (and its tracks) a signed-in reader is allowed to watch
 * online. A video with no tracks is simply not watchable — 404, not an error.
 */
class VideoReaderService
{
    public function __construct(
        private readonly VideoRepositoryInterface $videos,
    ) {}

    /** @throws NotFoundHttpException */
    public function video(string $slug): Video
    {
        $video = $this->videos->findBySlug($slug);

        if ($video === null || $video->tracks->isEmpty()) {
            throw new NotFoundHttpException();
        }

        return $video;
    }

    /** @throws NotFoundHttpException */
    public function track(Video $video, int $trackId): VideoTrack
    {
        $track = $video->tracks->firstWhere('id', $trackId);

        if ($track === null) {
            throw new NotFoundHttpException();
        }

        return $track;
    }
}
