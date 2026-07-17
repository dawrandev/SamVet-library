<?php

namespace App\Services\Lookups;

use App\Repositories\Eloquent\PostBranchRepository;

class PostBranchService extends BaseLookupService
{
    public function __construct(PostBranchRepository $repository)
    {
        parent::__construct($repository);
    }
}
