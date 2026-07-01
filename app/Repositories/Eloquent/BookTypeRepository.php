<?php

namespace App\Repositories\Eloquent;

use App\Models\BookType;

class BookTypeRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return BookType::class;
    }
}
