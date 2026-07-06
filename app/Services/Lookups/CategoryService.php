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
     * A category is translatable + hierarchical (parent_id).
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
