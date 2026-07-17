<?php

namespace App\Repositories\Eloquent;

use App\Models\PostBranch;

class PostBranchRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return PostBranch::class;
    }

    protected function scopeIndex($query)
    {
        return $query->orderBy('name');
    }
}
