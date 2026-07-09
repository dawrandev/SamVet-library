<?php

namespace App\Repositories\Eloquent;

use App\Enums\PublicationKind;
use App\Models\Article;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Journal;
use App\Models\JournalIssue;
use App\Models\News;
use App\Models\Reader;
use App\Repositories\Contracts\StatisticsRepositoryInterface;

class StatisticsRepository implements StatisticsRepositoryInterface
{
    public function totals(): array
    {
        // Journals and newspapers share one table — count both kinds in one query.
        $periodicals = Journal::query()
            ->selectRaw('kind, COUNT(*) as c')
            ->groupBy('kind')
            ->pluck('c', 'kind');

        return [
            'copies' => BookCopy::count(),
            'titles' => Book::count(),
            'readers' => Reader::count(),
            'journals' => (int) ($periodicals[PublicationKind::Journal->value] ?? 0),
            'newspapers' => (int) ($periodicals[PublicationKind::Newspaper->value] ?? 0),
            'issues' => JournalIssue::count(),
            'articles' => Article::count(),
            'news' => News::whereNotNull('published_at')->count(),
            'authors' => Author::count(),
        ];
    }
}
