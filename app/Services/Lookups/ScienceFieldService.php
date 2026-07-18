<?php

namespace App\Services\Lookups;

use App\Repositories\Eloquent\ScienceFieldRepository;

class ScienceFieldService extends BaseLookupService
{
    public function __construct(ScienceFieldRepository $repository)
    {
        parent::__construct($repository);
    }
}
