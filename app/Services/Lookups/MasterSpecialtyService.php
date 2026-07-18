<?php

namespace App\Services\Lookups;

use App\Repositories\Eloquent\MasterSpecialtyRepository;

class MasterSpecialtyService extends BaseLookupService
{
    public function __construct(MasterSpecialtyRepository $repository)
    {
        parent::__construct($repository);
    }
}
