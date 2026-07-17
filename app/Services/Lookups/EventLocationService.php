<?php

namespace App\Services\Lookups;

use App\Repositories\Eloquent\EventLocationRepository;

class EventLocationService extends BaseLookupService
{
    public function __construct(EventLocationRepository $repository)
    {
        parent::__construct($repository);
    }
}
