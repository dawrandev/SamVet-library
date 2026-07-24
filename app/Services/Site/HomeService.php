<?php

namespace App\Services\Site;

use App\Enums\CopyStatus;
use App\Enums\PublicationKind;
use App\Models\Article;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\BookType;
use App\Models\Journal;
use App\Models\News;
use App\Models\Reader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Aggregates the data shown on the public home page (statistics band,
 * collection tiles, featured book rows and latest news).
 */
class HomeService
{
    private const FEATURED_LIMIT = 5;
    private const NEWS_LIMIT = 4;

    public function __construct(
        private readonly SectionService $sections,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function homeData(): array
    {
        return [
            'stats' => $this->stats(),
            'collectionTiles' => $this->sections->tiles(),
            'mostRead' => $this->mostRead(),
            'newArrivals' => $this->newArrivals(),
            'latestNews' => $this->latestNews(),
        ];
    }

    /**
     * Fund statistics band.
     *
     * @return array<string, int>
     */
    private function stats(): array
    {
        return [
            'copies' => BookCopy::count(),
            'titles' => Book::count(),
            'readers' => Reader::count(),
            'periodicals' => Journal::count(),
            'articles' => Article::count(),
        ];
    }

    /**
     * Most-viewed books.
     *
     * @return Collection<int, Book>
     */
    private function mostRead(): Collection
    {
        return Book::query()
            ->with(['type'])
            ->withCount(['copies as available_copies' => fn (Builder $q) => $q->where('status', CopyStatus::Available->value)])
            ->where('views_count', '>', 0)
            ->orderByDesc('views_count')
            ->limit(self::FEATURED_LIMIT)
            ->get();
    }

    /**
     * Newest additions to the fund.
     *
     * @return Collection<int, Book>
     */
    private function newArrivals(): Collection
    {
        return Book::query()
            ->with(['type'])
            ->withCount(['copies as available_copies' => fn (Builder $q) => $q->where('status', CopyStatus::Available->value)])
            ->latest('id')
            ->limit(self::FEATURED_LIMIT)
            ->get();
    }

    /**
     * Latest published news.
     *
     * @return Collection<int, News>
     */
    private function latestNews(): Collection
    {
        return News::query()
            ->with('category')
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->limit(self::NEWS_LIMIT)
            ->get();
    }
}
