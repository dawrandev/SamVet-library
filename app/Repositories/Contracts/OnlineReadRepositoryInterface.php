<?php

namespace App\Repositories\Contracts;

use App\Models\OnlineRead;
use App\Models\Reader;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface OnlineReadRepositoryInterface
{
    public function log(Reader $reader, Model $readable): OnlineRead;

    /** One reader's online-reading history, most recent first. */
    public function paginateForReader(int $readerId, int $perPage = 10): LengthAwarePaginator;
}
