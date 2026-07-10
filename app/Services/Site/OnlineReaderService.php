<?php

namespace App\Services\Site;

use App\Models\Article;
use App\Models\Book;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use App\Repositories\Contracts\PeriodicalRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Resolves the material a signed-in reader is allowed to read online.
 * A record without a stored PDF is simply not readable — 404, not an error.
 */
class OnlineReaderService
{
    public function __construct(
        private readonly CatalogRepositoryInterface $catalog,
        private readonly PeriodicalRepositoryInterface $periodicals,
    ) {}

    /** @throws NotFoundHttpException */
    public function book(string $slug): Book
    {
        $book = $this->catalog->findPublicBySlug($slug);

        if ($book === null || blank($book->electronic_file)) {
            throw new NotFoundHttpException();
        }

        return $book;
    }

    /** @throws NotFoundHttpException */
    public function article(string $slug): Article
    {
        $article = $this->periodicals->findArticleBySlug($slug);

        if ($article === null || blank($article->electronic_file)) {
            throw new NotFoundHttpException();
        }

        return $article;
    }
}
