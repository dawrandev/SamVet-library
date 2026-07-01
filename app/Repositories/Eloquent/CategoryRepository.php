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
     * Ierarxiya uchun ota kategoriyani ham yuklaymiz (N+1 oldini olish).
     */
    protected function scopeIndex($query)
    {
        return $query->with('parent')->orderBy('id');
    }
}
