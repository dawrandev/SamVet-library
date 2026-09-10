<?php

namespace App\Services\Site;

use App\Models\Article;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Repositories\Contracts\PeriodicalRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Builds the public article (maqola) pages: the catalog listing, and a
 * detail page with the article's record, its online-reading availability,
 * and the other articles in the same issue.
 *
 * Two repositories on purpose: the listing reuses ArticleRepository, which
 * already carries the normalized search and eager loading the admin list
 * needs, while slug lookup and view counting live on PeriodicalRepository
 * alongside the journal/issue reads they belong with.
 */
class ArticlePageService
{
    public function __construct(
        private readonly PeriodicalRepositoryInterface $periodicals,
        private readonly ArticleRepositoryInterface $articles,
    ) {}

    /**
     * Every article, journal-held and external alike — an external one has no
     * issue page to appear on, so this is its only listing.
     *
     * @param  array{search?: string}  $filters
     */
    public function index(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        return $this->articles->paginate($filters, $perPage);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws NotFoundHttpException when no article matches the slug
     */
    public function show(string $slug): array
    {
        $article = $this->periodicals->findArticleBySlug($slug);

        if ($article === null) {
            throw new NotFoundHttpException;
        }

        $this->periodicals->incrementArticleViews($article);

        return [
            'article' => $article,
            'hasOnline' => filled($article->electronic_file),
            'others' => $this->otherArticles($article),
        ];
    }

    /**
     * Sibling articles in the same issue, keeping each one's ordinal position
     * within the issue (so the numbering matches the journal page).
     *
     * @return Collection<int, array{number: int, article: Article}>
     */
    private function otherArticles(Article $article): Collection
    {
        // A library-external article (no journal_issue_id) has no siblings.
        if ($article->journalIssue === null) {
            return collect();
        }

        return $this->periodicals->issueArticles($article->journalIssue)
            ->values()
            ->map(fn (Article $item, int $index): array => ['number' => $index + 1, 'article' => $item])
            ->reject(fn (array $row): bool => $row['article']->getKey() === $article->getKey())
            ->values();
    }
}
