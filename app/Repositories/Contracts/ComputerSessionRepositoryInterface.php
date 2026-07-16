<?php

namespace App\Repositories\Contracts;

use App\Models\ComputerSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ComputerSessionRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ComputerSession;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ComputerSession $session, array $data): ComputerSession;

    public function delete(ComputerSession $session): void;

    /**
     * @param  array{scope?: string, search?: string}  $filters
     */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function expiredCount(): int;
}
