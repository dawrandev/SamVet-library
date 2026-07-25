<?php

namespace App\Repositories\Contracts;

use App\Models\Computer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ComputerRepositoryInterface
{
    /**
     * Filtered, paginated list of computers.
     *
     * @param  array{search?: string, type?: string, status?: string, location?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Publicly hand-out-numbered computers (excludes internal/unnumbered
     * machines), grouped by their location enum value, each with its
     * current unfinished session (if any) eager-loaded for occupancy.
     *
     * @return Collection<string, Collection<int, Computer>>
     */
    public function publicByLocation(): Collection;

    public function find(int $id): ?Computer;

    public function create(array $data): Computer;

    public function update(Computer $computer, array $data): Computer;

    public function delete(Computer $computer): void;
}
