<?php

namespace App\Services\Site;

use App\Repositories\Contracts\CatalogRepositoryInterface;
use App\Repositories\Contracts\StatisticsRepositoryInterface;
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
            'byType' => $this->breakdown($this->catalog->typeFacets()),
            'byLanguage' => $this->breakdown($this->catalog->languageFacets()),
            'byCategory' => $this->breakdown($this->catalog->categoryFacets()),
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
}
