<?php

namespace App\Services\Lookups;

use App\Data\LookupData;
use App\Repositories\Eloquent\CategoryRepository;

class CategoryService extends BaseLookupService
{
    public function __construct(CategoryRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Kategoriya tarjimali + ierarxik (parent_id) bo'ladi.
     *
     * @return array<string, mixed>
     */
    protected function attributes(LookupData $data): array
    {
        return [
            'name' => $data->name,
            'parent_id' => $data->parent_id,
        ];
    }
}
