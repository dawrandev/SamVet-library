<?php

namespace App\Repositories\Eloquent;

use App\Models\DepartmentCoverage;

class DepartmentCoverageRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return DepartmentCoverage::class;
    }

    /** Stable insertion order — not "latest first" like most lookups. */
    protected function scopeIndex($query)
    {
        return $query->orderBy('id');
    }
}
