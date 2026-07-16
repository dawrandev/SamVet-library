<?php

namespace App\Services\Site;

use App\Models\Article;
use App\Repositories\Contracts\PeriodicalRepositoryInterface;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Builds the public article (maqola) detail page: the article's record, its
 * online-reading availability, and the other articles in the same issue.
 */
class ArticlePageService
{
    public function __construct(
        private readonly PeriodicalRepositoryInterface $periodicals,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws NotFoundHttpException when no article matches the slug
     */
    public function show(string $slug): array
    {
        $article = $this->periodicals->findArticleBySlug($slug);

        if ($article === null) {
            throw new NotFoundHttpException();
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
