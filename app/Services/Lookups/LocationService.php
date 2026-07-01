<?php

namespace App\Services\Lookups;

use App\Repositories\Eloquent\LocationRepository;

class LocationService extends BaseLookupService
{
    public function __construct(LocationRepository $repository)
    {
        parent::__construct($repository);
    }
}
