<?php

namespace App\Repositories\Contracts;

use App\Models\Article;
use App\Models\Journal;
use App\Models\JournalIssue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Read-only data access for the public periodicals section
 * (journals / newspapers, their issues and articles).
 */
interface PeriodicalRepositoryInterface
{
    /** Paginated periodicals, optionally narrowed to one kind (journal / newspaper). */
    public function paginateJournals(?string $kind, int $perPage): LengthAwarePaginator;

    /** A single public journal (by slug) with issues (article counts) eager-loaded. */
    public function findJournalBySlug(string $slug): ?Journal;

    /**
     * Articles of one issue, ordered by starting page.
     *
     * @return Collection<int, Article>
     */
    public function issueArticles(JournalIssue $issue): Collection;

    /** A single public article (by slug) with its issue/journal chain loaded. */
    public function findArticleBySlug(string $slug): ?Article;

    /** Register one article page view. */
    public function incrementArticleViews(Article $article): void;
}
