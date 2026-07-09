<?php

namespace App\Repositories\Eloquent;

use App\Models\Article;
use App\Models\Journal;
use App\Models\JournalIssue;
use App\Repositories\Contracts\PeriodicalRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PeriodicalRepository implements PeriodicalRepositoryInterface
{
    public function findJournalBySlug(string $slug): ?Journal
    {
        return Journal::query()
            ->with([
                'type',
                'language',
                'publisher',
                'issues' => fn ($q) => $q->withCount('articles'),
            ])
            ->where('slug', $slug)
            ->first();
    }

    public function issueArticles(JournalIssue $issue): Collection
    {
        return Article::query()
            ->with('language')
            ->where('journal_issue_id', $issue->id)
            ->orderByRaw('CAST(pages AS UNSIGNED)')
            ->orderBy('id')
            ->get();
    }

    public function findArticleBySlug(string $slug): ?Article
    {
        return Article::query()
            ->with([
                'journalIssue.journal.type',
                'journalIssue.journal.language',
                'journalIssue.journal.publisher',
                'resourceField',
                'language',
            ])
            ->where('slug', $slug)
            ->first();
    }

    public function incrementArticleViews(Article $article): void
    {
        $article->increment('views_count');
    }
}
