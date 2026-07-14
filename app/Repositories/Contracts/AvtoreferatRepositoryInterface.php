<?php

namespace App\Repositories\Contracts;

use App\Models\Avtoreferat;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AvtoreferatRepositoryInterface
{
    /**
     * Filtered, paginated list of avtoreferats.
     *
     * @param  array{search?: string, resource_field_id?: int}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?Avtoreferat;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Avtoreferat;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Avtoreferat $avtoreferat, array $data): Avtoreferat;

    public function delete(Avtoreferat $avtoreferat): void;
}
