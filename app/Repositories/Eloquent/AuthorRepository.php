<?php

namespace App\Repositories\Eloquent;

use App\Models\Author;

class AuthorRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return Author::class;
    }

    protected function scopeIndex($query)
    {
        return $query->orderBy('name');
    }
}
