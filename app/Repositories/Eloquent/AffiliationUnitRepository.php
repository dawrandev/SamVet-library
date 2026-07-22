<?php

namespace App\Repositories\Eloquent;

use App\Models\AffiliationUnit;

class AffiliationUnitRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return AffiliationUnit::class;
    }

    protected function scopeIndex($query)
    {
        return $query->orderBy('name');
    }
}
