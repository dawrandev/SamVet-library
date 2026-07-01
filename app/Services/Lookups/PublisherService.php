<?php

namespace App\Services\Lookups;

use App\Repositories\Eloquent\PublisherRepository;

class PublisherService extends BaseLookupService
{
    public function __construct(PublisherRepository $repository)
    {
        parent::__construct($repository);
    }
}
