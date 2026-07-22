<?php

namespace App\Services\Lookups;

use App\Repositories\Eloquent\AffiliationPlaceRepository;

class AffiliationPlaceService extends BaseLookupService
{
    public function __construct(AffiliationPlaceRepository $repository)
    {
        parent::__construct($repository);
    }
}
