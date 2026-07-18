<?php

namespace App\Repositories\Eloquent;

use App\Models\ScienceField;

class ScienceFieldRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return ScienceField::class;
    }

    protected function scopeIndex($query)
    {
        return $query->orderBy('name');
    }
}
