<?php

namespace App\Repositories\Contracts;

use App\Models\News;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface NewsRepositoryInterface
{
    /**
     * Filtered, paginated list of news.
     *
     * @param  array{search?: string, news_category_id?: int, status?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?News;

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
