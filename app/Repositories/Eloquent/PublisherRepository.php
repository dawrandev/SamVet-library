<?php

namespace App\Repositories\Eloquent;

use App\Models\Publisher;

class PublisherRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return Publisher::class;
    }

    protected function scopeIndex($query)
    {
        return $query->orderBy('name');
    }
}
