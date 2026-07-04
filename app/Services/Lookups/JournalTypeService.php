<?php

namespace App\Services\Lookups;

use App\Repositories\Eloquent\JournalTypeRepository;

class JournalTypeService extends BaseLookupService
{
    public function __construct(JournalTypeRepository $repository)
    {
        parent::__construct($repository);
    }
}
