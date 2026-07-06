<?php

namespace App\Services\Lookups;

use App\Repositories\Eloquent\ResourceFieldRepository;

class ResourceFieldService extends BaseLookupService
{
    public function __construct(ResourceFieldRepository $repository)
    {
        parent::__construct($repository);
    }
}
