<?php

namespace App\Services\Site;

use App\Repositories\Contracts\VideoRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Builds the public video catalog + detail pages.
 */
class VideoPageService
{
    private const SIMILAR_LIMIT = 4;

    public function __construct(
        private readonly VideoRepositoryInterface $videos,
    ) {}

    /**
     * @param  array{search?: string}  $filters
     */
    public function index(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        return $this->videos->paginate($filters, $perPage);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws NotFoundHttpException when no video matches the slug
     */
    public function show(string $slug): array
    {
        $video = $this->videos->findBySlug($slug);

        if ($video === null) {
            throw new NotFoundHttpException();
        }

        $this->videos->incrementViews($video);

        return [
            'video' => $video,
            'similar' => $this->videos->similar($video, self::SIMILAR_LIMIT),
        ];
    }
}
