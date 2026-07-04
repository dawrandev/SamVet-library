<?php

namespace App\Repositories\Eloquent;

use App\Models\JournalType;

class JournalTypeRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return JournalType::class;
    }
}
