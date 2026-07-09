<?php

namespace App\Services\Site;

use App\Repositories\Contracts\PeriodicalRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Builds the public journal/newspaper detail page: masthead info, the list of
 * issues, and the articles of the currently selected issue.
 */
class JournalPageService
{
    public function __construct(
        private readonly PeriodicalRepositoryInterface $periodicals,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws NotFoundHttpException when no journal matches the slug
     */
    public function show(string $slug, ?int $issueId): array
    {
        $journal = $this->periodicals->findJournalBySlug($slug);

        if ($journal === null) {
            throw new NotFoundHttpException();
        }

        // Issues are already loaded (latest year first); pick the requested one,
        // falling back to the newest. Matching against the loaded set also
        // guarantees the issue actually belongs to this journal.
        $issues = $journal->issues;
        $selected = $issueId !== null ? $issues->firstWhere('id', $issueId) : null;
        $selected ??= $issues->first();

        return [
            'journal' => $journal,
            'issues' => $issues,
            'selected' => $selected,
            'articles' => $selected ? $this->periodicals->issueArticles($selected) : collect(),
            'sinceYear' => $issues->min('year'),
        ];
    }
}
