<?php

namespace App\Services\Lookups;

use App\Repositories\Eloquent\DeliveryLocationRepository;

class DeliveryLocationService extends BaseLookupService
{
    public function __construct(DeliveryLocationRepository $repository)
    {
        parent::__construct($repository);
    }
}
