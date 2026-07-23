<?php

namespace App\Repositories\Contracts;

use App\Models\Dissertation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

interface DissertationRepositoryInterface
{
    /**
     * Filtered (unpaginated) query builder — shared by the paginated list and exports.
     *
     * @param  array{search?: string, resource_field_id?: int}  $filters
     */
    public function filtered(array $filters = []): Builder;

    /**
     * Filtered, paginated list of dissertations.
     *
     * @param  array{search?: string, resource_field_id?: int}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?Dissertation;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Dissertation;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Dissertation $dissertation, array $data): Dissertation;

    public function delete(Dissertation $dissertation): void;
}
