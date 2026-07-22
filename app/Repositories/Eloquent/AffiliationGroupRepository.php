<?php

namespace App\Repositories\Eloquent;

use App\Models\AffiliationGroup;

class AffiliationGroupRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return AffiliationGroup::class;
    }

    protected function scopeIndex($query)
    {
        return $query->orderBy('name');
    }
}
