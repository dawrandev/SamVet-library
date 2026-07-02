<?php

namespace App\Repositories\Contracts;

use App\Models\Reader;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ReaderRepositoryInterface
{
    /**
     * Filtrlangan, sahifalangan a'zolar ro'yxati.
     *
     * @param  array{search?: string, type?: string, status?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function find(int $id): ?Reader;

    public function create(array $data): Reader;

    public function update(Reader $reader, array $data): Reader;

    public function delete(Reader $reader): void;
}
