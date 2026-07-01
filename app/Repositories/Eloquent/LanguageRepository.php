<?php

namespace App\Repositories\Eloquent;

use App\Models\Language;

class LanguageRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return Language::class;
    }
}
