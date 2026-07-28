<?php

namespace App\Services\Site;

use App\Repositories\Contracts\CatalogRepositoryInterface;
use App\Repositories\Contracts\StatisticsRepositoryInterface;
use App\Services\ReaderStatsService;
use Illuminate\Support\Collection;

/**
 * Public statistics page: live totals from the database plus the few facts
 * (founding year, reading-room seats) that live in config.
 */
class StatisticsService
{
    private const BREAKDOWN_LIMIT = 8;

    public function __construct(
        private readonly StatisticsRepositoryInterface $statistics,
        private readonly CatalogRepositoryInterface $catalog,
        private readonly ReaderStatsService $readerStats,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function statisticsData(): array
    {
        return [
            'totals' => $this->statistics->totals(),
            'facts' => [
                'founded_year' => config('arm.founded_year'),
                'reading_room_seats' => config('arm.reading_room_seats'),
            ],
            // Fund breakdowns — kategoriya, shakli (format), til.
            'byType' => $this->breakdown($this->catalog->typeFacets()),
            'byFormat' => $this->breakdown($this->catalog->formatFacets()),
            'byLanguage' => $this->breakdown($this->catalog->languageFacets()),
            'byCategory' => $this->breakdown($this->catalog->categoryFacets()),
            // Reader demographics — turi, jinsi, yoshi.
            'readersByType' => $this->labelledBreakdown($this->readerStats->byType()),
            'readersByGender' => $this->labelledBreakdown($this->readerStats->byGender()),
            'readersByAgeGroup' => $this->labelledBreakdown($this->readerStats->byAgeGroup()),
        ];
    }

    /**
     * Drop empty buckets, keep the largest ones and attach a bar share (0-100).
     *
     * @param  Collection<int, array{id: int, label: string, count: int}>  $facets
     * @return Collection<int, array{label: string, count: int, share: float}>
     */
    private function breakdown(Collection $facets): Collection
    {
        $rows = $facets
            ->filter(fn (array $facet): bool => $facet['count'] > 0)
            ->sortByDesc('count')
            ->take(self::BREAKDOWN_LIMIT)
            ->values();

        $max = (int) $rows->max('count') ?: 1;

        return $rows->map(fn (array $facet): array => [
            'label' => $facet['label'],
            'count' => $facet['count'],
            'share' => round($facet['count'] / $max * 100, 1),
        ]);
    }

    /**
     * Same {label, count, share} shape as breakdown(), but for a plain
     * label => count array (reader demographics) — and, unlike breakdown(),
     * keeps the source's own order rather than sorting by size. Age buckets
     * in particular must stay chronological (<18, 18-25, ...), not
     * largest-first.
     *
     * @param  array<string, int>  $counts
     * @return Collection<int, array{label: string, count: int, share: float}>
     */
    private function labelledBreakdown(array $counts): Collection
    {
        $max = $counts === [] ? 1 : max($counts);

        return collect($counts)->map(fn (int $count, string $label): array => [
            'label' => $label,
            'count' => $count,
            'share' => round($count / $max * 100, 1),
        ])->values();
    }
}
