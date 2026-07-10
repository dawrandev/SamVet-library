<?php

namespace App\Services\Lookups;

use App\Repositories\Eloquent\PublicationPlaceRepository;

class PublicationPlaceService extends BaseLookupService
{
    public function __construct(PublicationPlaceRepository $repository)
    {
        parent::__construct($repository);
    }
}
