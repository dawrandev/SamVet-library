<?php

namespace App\Repositories\Eloquent;

use App\Models\EventLocation;

class EventLocationRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return EventLocation::class;
    }

    protected function scopeIndex($query)
    {
        return $query->orderBy('name');
    }
}
