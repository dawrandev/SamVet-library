<?php

namespace App\Services\Lookups;

use App\Repositories\Eloquent\DoctoralSpecialtyRepository;

class DoctoralSpecialtyService extends BaseLookupService
{
    public function __construct(DoctoralSpecialtyRepository $repository)
    {
        parent::__construct($repository);
    }
}
