<?php

namespace App\Repositories\Eloquent;

use App\Models\NewsCategory;

class NewsCategoryRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return NewsCategory::class;
    }
}
