<?php

namespace App\Repositories\Eloquent;

use App\Models\Region;

class RegionRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return Region::class;
    }

    protected function scopeIndex($query)
    {
        return $query->orderBy('name');
    }
}
