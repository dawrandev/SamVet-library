<?php

namespace App\Services\Site;

use App\Models\Audiobook;
use App\Models\AudioTrack;
use App\Repositories\Contracts\AudiobookRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Resolves the audiobook (and its tracks) a signed-in reader is allowed to
 * listen to online. An audiobook with no tracks is simply not listenable —
 * 404, not an error.
 */
class AudioReaderService
{
    public function __construct(
        private readonly AudiobookRepositoryInterface $audiobooks,
    ) {}

    /** @throws NotFoundHttpException */
    public function audiobook(string $slug): Audiobook
    {
        $audiobook = $this->audiobooks->findBySlug($slug);

        if ($audiobook === null || $audiobook->tracks->isEmpty()) {
            throw new NotFoundHttpException();
        }

        return $audiobook;
    }

    /** @throws NotFoundHttpException */
    public function track(Audiobook $audiobook, int $trackId): AudioTrack
    {
        $track = $audiobook->tracks->firstWhere('id', $trackId);

        if ($track === null) {
            throw new NotFoundHttpException();
        }

        return $track;
    }
}
