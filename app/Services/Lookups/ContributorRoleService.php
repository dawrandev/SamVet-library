<?php

namespace App\Services\Lookups;

use App\Repositories\Eloquent\ContributorRoleRepository;

class ContributorRoleService extends BaseLookupService
{
    public function __construct(ContributorRoleRepository $repository)
    {
        parent::__construct($repository);
    }
}
