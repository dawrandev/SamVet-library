<?php

namespace App\Repositories\Eloquent;

use App\Models\District;

class DistrictRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return District::class;
    }

    /**
     * Also load the region for the hierarchy (avoid N+1).
     */
    protected function scopeIndex($query)
    {
        return $query->with('region')->orderBy('name');
    }
}
