<?php

namespace App\Repositories\Contracts;

use App\Models\Computer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ComputerRepositoryInterface
{
    /**
     * Filtered, paginated list of computers.
     *
     * @param  array{search?: string, type?: string, status?: string, location?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?Computer;

    public function create(array $data): Computer;

    public function update(Computer $computer, array $data): Computer;

    public function delete(Computer $computer): void;
}
