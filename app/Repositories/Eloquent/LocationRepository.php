<?php

namespace App\Repositories\Eloquent;

use App\Models\Location;

class LocationRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return Location::class;
    }
}
