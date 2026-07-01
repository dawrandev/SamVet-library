<?php

namespace App\Services\Lookups;

use App\Repositories\Eloquent\BookTypeRepository;

class BookTypeService extends BaseLookupService
{
    public function __construct(BookTypeRepository $repository)
    {
        parent::__construct($repository);
    }
}
