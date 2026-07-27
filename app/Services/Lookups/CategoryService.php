<?php

namespace App\Services\Lookups;

use App\Data\LookupData;
use App\Models\Category;
use App\Repositories\Eloquent\CategoryRepository;
use Illuminate\Database\Eloquent\Model;

class CategoryService extends BaseLookupService
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    ) {
        parent::__construct($categoryRepository);
    }

    /** New categories append to the end of their sibling group, never jump to the front. */
    public function create(LookupData $data): Model
    {
        $nextOrder = (Category::where('parent_id', $data->parent_id)->max('sort_order') ?? -1) + 1;

        return $this->categoryRepository->create([...$this->attributes($data), 'sort_order' => $nextOrder]);
    }

    public function moveUp(Category $category): void
    {
        $this->categoryRepository->moveUp($category);
    }

    public function moveDown(Category $category): void
    {
        $this->categoryRepository->moveDown($category);
    }

    /**
     * A category is translatable + hierarchical (parent_id). sort_order is
     * deliberately excluded here — it's only ever set on create() (append)
     * or via moveUp()/moveDown(), never overwritten by an ordinary edit.
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
