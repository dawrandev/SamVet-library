<?php

namespace App\Repositories\Eloquent;

use App\Models\PublicationPlace;

class PublicationPlaceRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return PublicationPlace::class;
    }
}
