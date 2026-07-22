<?php

namespace App\Services\Lookups;

use App\Repositories\Eloquent\AffiliationGroupRepository;

class AffiliationGroupService extends BaseLookupService
{
    public function __construct(AffiliationGroupRepository $repository)
    {
        parent::__construct($repository);
    }
}
