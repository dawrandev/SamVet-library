<?php

namespace App\Services\Site;

use App\Repositories\Contracts\AudiobookRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Builds the public audiobook catalog + detail pages.
 */
class AudiobookPageService
{
    private const SIMILAR_LIMIT = 4;

    public function __construct(
        private readonly AudiobookRepositoryInterface $audiobooks,
    ) {}

    /**
     * @param  array{search?: string}  $filters
     */
    public function index(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        return $this->audiobooks->paginate($filters, $perPage);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws NotFoundHttpException when no audiobook matches the slug
     */
    public function show(string $slug): array
    {
        $audiobook = $this->audiobooks->findBySlug($slug);

        if ($audiobook === null) {
            throw new NotFoundHttpException();
        }

        $this->audiobooks->incrementViews($audiobook);

        return [
            'audiobook' => $audiobook,
            'similar' => $this->audiobooks->similar($audiobook, self::SIMILAR_LIMIT),
        ];
    }
}
