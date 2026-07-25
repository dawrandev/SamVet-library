<?php

namespace App\Repositories\Eloquent;

use App\Models\ReaderType;

class ReaderTypeRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return ReaderType::class;
    }

    protected function scopeIndex($query)
    {
        // Insertion order matters here (e.g. the three Bakalavr shifts stay
        // grouped together) — not alphabetical, unlike most other lookups.
        return $query->orderBy('id');
    }
}
