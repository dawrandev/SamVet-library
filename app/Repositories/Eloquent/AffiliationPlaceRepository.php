<?php

namespace App\Repositories\Eloquent;

use App\Models\AffiliationPlace;

class AffiliationPlaceRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return AffiliationPlace::class;
    }

    protected function scopeIndex($query)
    {
        return $query->orderBy('name');
    }
}
