<?php

namespace App\Repositories\Eloquent;

use App\Models\DoctoralSpecialty;

class DoctoralSpecialtyRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return DoctoralSpecialty::class;
    }

    protected function scopeIndex($query)
    {
        return $query->orderBy('name');
    }
}
