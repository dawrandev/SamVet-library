<?php

namespace App\Services\Lookups;

use App\Repositories\Eloquent\AuthorRepository;

class AuthorService extends BaseLookupService
{
    public function __construct(AuthorRepository $repository)
    {
        parent::__construct($repository);
    }
}
