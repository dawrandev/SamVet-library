<?php

namespace App\Repositories\Contracts;

use App\Models\ReaderWarning;

interface WarningRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ReaderWarning;

    public function delete(ReaderWarning $warning): void;
}
