<?php

namespace App\Repositories\Contracts;

use App\Models\Book;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

interface BookRepositoryInterface
{
    /**
     * Filtered (unpaginated) query builder — shared by the paginated list and exports.
     *
     * @param  array{search?: string, category_id?: int, language_id?: int}  $filters
     */
    public function filtered(array $filters = []): Builder;

    /**
     * Filtered, paginated list of books.
     *
     * @param  array{search?: string, category_id?: int, language_id?: int}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?Book;

    public function create(array $data): Book;

    public function update(Book $book, array $data): Book;

    public function delete(Book $book): void;
}
