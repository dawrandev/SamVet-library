<?php

namespace App\Services\Lookups;

use App\Repositories\Eloquent\AffiliationUnitRepository;

class AffiliationUnitService extends BaseLookupService
{
    public function __construct(AffiliationUnitRepository $repository)
    {
        parent::__construct($repository);
    }
}
