<?php

namespace App\Repositories\Contracts;

use App\Models\Audiobook;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Audiobook is small enough (no drafts, no admin-only fields) that one
 * repository serves both the admin CRUD and the public site — unlike Book,
 * which splits admin (BookRepositoryInterface) from site (CatalogRepositoryInterface)
 * because of its faceted catalog search and admin-only fields.
 */
interface AudiobookRepositoryInterface
{
    /**
     * Filtered (unpaginated) query builder — shared by the paginated list and exports.
     *
     * @param  array{search?: string}  $filters
     */
    public function filtered(array $filters = []): Builder;

    /**
     * Filtered, paginated list of audiobooks (name/author search).
     *
     * @param  array{search?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?Audiobook;

    public function findBySlug(string $slug): ?Audiobook;

    public function create(array $data): Audiobook;

    public function update(Audiobook $audiobook, array $data): Audiobook;

    public function delete(Audiobook $audiobook): void;

    /** Register one detail-page view. */
    public function incrementViews(Audiobook $audiobook): void;

    /**
     * A handful of other audiobooks to surface as "similar" on the detail page.
     *
     * @return Collection<int, Audiobook>
     */
    public function similar(Audiobook $audiobook, int $limit): Collection;
}
