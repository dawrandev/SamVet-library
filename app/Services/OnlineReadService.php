<?php

namespace App\Services;

use App\Models\OnlineRead;
use App\Models\Reader;
use App\Repositories\Contracts\OnlineReadRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class OnlineReadService
{
    public function __construct(
        private readonly OnlineReadRepositoryInterface $reads,
    ) {}

    public function log(Reader $reader, Model $readable): OnlineRead
    {
        return $this->reads->log($reader, $readable);
    }

    public function paginateForReader(int $readerId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->reads->paginateForReader($readerId, $perPage);
    }
}
