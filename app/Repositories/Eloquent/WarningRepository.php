<?php

namespace App\Repositories\Eloquent;

use App\Models\ReaderWarning;
use App\Repositories\Contracts\WarningRepositoryInterface;

class WarningRepository implements WarningRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ReaderWarning
    {
        return ReaderWarning::create($data);
    }

    public function delete(ReaderWarning $warning): void
    {
        $warning->delete();
    }
}
