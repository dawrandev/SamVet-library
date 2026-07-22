<?php

namespace App\Services\Lookups;

use App\Repositories\Eloquent\RegionRepository;

class RegionService extends BaseLookupService
{
    public function __construct(RegionRepository $repository)
    {
        parent::__construct($repository);
    }
}
