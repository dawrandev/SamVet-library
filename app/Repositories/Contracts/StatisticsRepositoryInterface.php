<?php

namespace App\Repositories\Contracts;

/**
 * Aggregate counts for the public statistics page.
 */
interface StatisticsRepositoryInterface
{
    /**
     * Fund and usage totals.
     *
     * @return array{copies: int, titles: int, readers: int, journals: int, newspapers: int, issues: int, articles: int, news: int}
     */
    public function totals(): array;
}
