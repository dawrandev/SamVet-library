<?php

namespace App\Repositories\Contracts;

use App\Models\News;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface NewsRepositoryInterface
{
    /**
     * Filtered, paginated list of news.
     *
     * @param  array{search?: string, news_category_id?: int, status?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?News;

    /* ----- Public site reads (published news only) ----- */

    /** Paginated published news, optionally narrowed to one category. */
    public function publishedPaginated(?int $categoryId, int $perPage): LengthAwarePaginator;

    /**
     * Categories that have at least one published item (for the filter chips).
     *
     * @return Collection<int, array{id: int, label: string}>
     */
    public function publishedCategories(): Collection;

    /** A single published news item (by slug) with category and gallery loaded. */
    public function findPublishedBySlug(string $slug): ?News;

    /**
     * Latest published news excluding the given id (for the "related" row).
     *
     * @return Collection<int, News>
     */
    public function latestPublishedExcept(int $exceptId, int $limit): Collection;

    /** Register one page view. */
    public function incrementViews(News $news): void;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): News;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(News $news, array $data): News;

    public function delete(News $news): void;
}
