<?php

namespace App\Services\Site;

use App\Repositories\Contracts\NewsRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public news feed: the paginated, category-filterable list and the single
 * news item (with a few related items).
 */
class NewsService
{
    private const PER_PAGE = 9;
    private const RELATED_LIMIT = 3;

    public function __construct(
        private readonly NewsRepositoryInterface $news,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function index(?int $categoryId): array
    {
        return [
            'news' => $this->news->publishedPaginated($categoryId, self::PER_PAGE),
            'categories' => $this->news->publishedCategories(),
            'activeCategory' => $categoryId,
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws NotFoundHttpException when no published news matches the slug
     */
    public function show(string $slug): array
    {
        $item = $this->news->findPublishedBySlug($slug);

        if ($item === null) {
            throw new NotFoundHttpException();
        }

        $this->news->incrementViews($item);

        return [
            'news' => $item,
            'related' => $this->news->latestPublishedExcept($item->id, self::RELATED_LIMIT),
        ];
    }
}
