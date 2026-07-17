<?php

namespace App\Repositories\Eloquent;

use App\Models\DeliveryLocation;

class DeliveryLocationRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return DeliveryLocation::class;
    }

    protected function scopeIndex($query)
    {
        return $query->orderBy('name');
    }
}
