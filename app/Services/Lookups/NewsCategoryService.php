<?php

namespace App\Services\Lookups;

use App\Repositories\Eloquent\NewsCategoryRepository;

class NewsCategoryService extends BaseLookupService
{
    public function __construct(NewsCategoryRepository $repository)
    {
        parent::__construct($repository);
    }
}
