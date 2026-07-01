<?php

namespace App\Services\Lookups;

use App\Repositories\Eloquent\LanguageRepository;

class LanguageService extends BaseLookupService
{
    public function __construct(LanguageRepository $repository)
    {
        parent::__construct($repository);
    }
}
