<?php

namespace App\Services\Site;

use App\Repositories\Contracts\CatalogRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Builds the public book detail page: the book itself (public fields only),
 * the formats it is held in, and a row of similar books. Also registers a view.
 */
class BookPageService
{
    private const SIMILAR_LIMIT = 4;

    public function __construct(
        private readonly CatalogRepositoryInterface $catalog,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws NotFoundHttpException when no book matches the slug
     */
    public function show(string $slug): array
    {
        $book = $this->catalog->findPublicBySlug($slug);

        if ($book === null) {
            throw new NotFoundHttpException();
        }

        $this->catalog->incrementViews($book);

        return [
            'book' => $book,
            'formats' => $this->catalog->formats($book),
            // Online reading is offered only when a protected PDF is on file.
            'hasOnline' => filled($book->electronic_file),
            'similar' => $this->catalog->similar($book, self::SIMILAR_LIMIT),
            // Client site only ever shows top-level categories — a book tagged with
            // a child category displays (and links to) that child's parent instead.
            'displayCategories' => $book->categories
                ->map(fn ($category) => $category->parent ?? $category)
                ->unique('id')
                ->values(),
        ];
    }
}
