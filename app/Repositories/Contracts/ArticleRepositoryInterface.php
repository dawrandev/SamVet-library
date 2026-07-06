<?php

namespace App\Repositories\Contracts;

use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ArticleRepositoryInterface
{
    /**
     * Filtered, paginated list of articles.
     *
     * @param  array{search?: string, journal_id?: int, resource_field_id?: int}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?Article;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Article;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Article $article, array $data): Article;

    public function delete(Article $article): void;
}
