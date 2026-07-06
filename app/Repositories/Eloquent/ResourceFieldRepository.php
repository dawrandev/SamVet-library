<?php

namespace App\Repositories\Eloquent;

use App\Models\ResourceField;

class ResourceFieldRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return ResourceField::class;
    }
}
