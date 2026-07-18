<?php

namespace App\Repositories\Eloquent;

use App\Models\MasterSpecialty;

class MasterSpecialtyRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return MasterSpecialty::class;
    }

    protected function scopeIndex($query)
    {
        return $query->orderBy('name');
    }
}
