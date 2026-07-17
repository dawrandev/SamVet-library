<?php

namespace App\Repositories\Eloquent;

use App\Models\ContributorRole;

class ContributorRoleRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return ContributorRole::class;
    }

    protected function scopeIndex($query)
    {
        return $query->orderBy('name');
    }
}
