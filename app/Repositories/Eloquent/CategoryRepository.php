<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;

class CategoryRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return Category::class;
    }

    /**
     * Also load the parent category for the hierarchy (avoid N+1).
     */
    protected function scopeIndex($query)
    {
        return $query->with('parent')->orderBy('id');
    }
}
