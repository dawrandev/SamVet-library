<?php

namespace App\Services\Client;

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

    /**
     * @return array<string, mixed>
     */
    public function homeData(): array
    {
        return [
            'stats' => $this->stats(),
            'collectionTiles' => $this->collectionTiles(),
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
     * "Elektron kutubxona bo'limlari" tiles: book counts per type + periodicals by kind.
     *
     * @return Collection<int, array{label: string, count: int, key: string}>
     */
    private function collectionTiles(): Collection
    {
        // One grouped query instead of a count per type.
        $countsByType = Book::query()
            ->selectRaw('book_type_id, COUNT(*) as c')
            ->groupBy('book_type_id')
            ->pluck('c', 'book_type_id');

        $tiles = BookType::query()
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn (BookType $type): array => [
                'key' => 'type-'.$type->id,
                'label' => $type->getTranslation('name', app()->getLocale(), false) ?: $type->getTranslation('name', 'uz', false),
                'count' => (int) ($countsByType[$type->id] ?? 0),
            ]);

        // Periodicals by kind (journals / newspapers).
        $periodicalCounts = Journal::query()
            ->selectRaw('kind, COUNT(*) as c')
            ->groupBy('kind')
            ->pluck('c', 'kind');

        return $tiles
            ->push([
                'key' => 'journals',
                'label' => __('Jurnallar'),
                'count' => (int) ($periodicalCounts[PublicationKind::Journal->value] ?? 0),
            ])
            ->push([
                'key' => 'newspapers',
                'label' => __('Gazetalar'),
                'count' => (int) ($periodicalCounts[PublicationKind::Newspaper->value] ?? 0),
            ])
            ->values();
    }

    /**
     * Most-viewed books.
     *
     * @return Collection<int, Book>
     */
    private function mostRead(): Collection
    {
        return Book::query()
            ->with(['type', 'authors'])
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
            ->with(['type', 'authors'])
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
