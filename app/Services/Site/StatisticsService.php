<?php

namespace App\Services\Site;

use App\Repositories\Contracts\CatalogRepositoryInterface;
use App\Repositories\Contracts\StatisticsRepositoryInterface;
use App\Repositories\Eloquent\DepartmentCoverageRepository;
use App\Services\AnnualAcquisitionReportService;
use App\Services\ReaderStatsService;
use Illuminate\Support\Collection;

/**
 * Public statistics page: live totals and breakdowns from the database.
 */
class StatisticsService
{
    private const BREAKDOWN_LIMIT = 8;

    public function __construct(
        private readonly StatisticsRepositoryInterface $statistics,
        private readonly CatalogRepositoryInterface $catalog,
        private readonly ReaderStatsService $readerStats,
        private readonly DepartmentCoverageRepository $departmentCoverages,
        private readonly AnnualAcquisitionReportService $annualAcquisitionReportService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function statisticsData(): array
    {
        return [
            'totals' => $this->statistics->totals(),
            // Fund breakdowns — kategoriya, shakli (format), til.
            'byType' => $this->breakdown($this->catalog->typeFacets()),
            'byFormat' => $this->breakdown($this->catalog->formatFacets()),
            'byLanguage' => $this->breakdown($this->catalog->languageFacets()),
            'byCategory' => $this->breakdown($this->catalog->categoryFacets()),
            // Reader demographics — turi, jinsi, yoshi.
            'readersByType' => $this->labelledBreakdown($this->readerStats->byType()),
            'readersByGender' => $this->labelledBreakdown($this->readerStats->byGender()),
            'readersByAgeGroup' => $this->labelledBreakdown($this->readerStats->byAgeGroup()),
            // Kafedralar kesimida ta'minganlik darajasi — admin-managed, not derived from counts.
            'byDepartment' => $this->departmentBreakdown(),
            // Yillik hisobot — same report/aggregation the admin dashboard shows, reused as-is.
            'annualReport' => $this->annualAcquisitionReportService->report(),
        ];
    }

    /**
     * Each department's own percentage IS its bar share — unlike the other
     * breakdowns, not relative to the largest value in the set.
     *
     * @return Collection<int, array{label: string, count: int, share: float}>
     */
    private function departmentBreakdown(): Collection
    {
        $locale = app()->getLocale();

        return $this->departmentCoverages->all()->map(fn ($department): array => [
            'label' => $department->getTranslation('name', $locale, false)
                ?: $department->getTranslation('name', 'uz', false),
            'count' => $department->percentage,
            'share' => $department->percentage,
        ])->values();
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
