<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository extends BaseLookupRepository
{
    protected function model(): string
    {
        return Category::class;
    }

    /**
     * A flat list, grouped so each parent is immediately followed by its own
     * children — both levels ordered by `sort_order` (falling back to `id`),
     * so the admin table mirrors the same order the public site uses.
     */
    public function all(): Collection
    {
        $parents = Category::query()
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->orderBy('sort_order')->orderBy('id')
            ->get();

        $flattened = $parents->flatMap(function (Category $parent) {
            $children = $parent->children->each(fn (Category $child) => $child->setRelation('parent', $parent));

            return collect([$parent])->concat($children);
        })->values();

        return Collection::make($flattened->all());
    }

    /** Swap sort_order with the previous sibling (same parent_id) — no-op if already first. */
    public function moveUp(Category $category): void
    {
        $this->swap($category, -1);
    }

    /** Swap sort_order with the next sibling (same parent_id) — no-op if already last. */
    public function moveDown(Category $category): void
    {
        $this->swap($category, 1);
    }

    private function swap(Category $category, int $direction): void
    {
        $siblings = Category::query()
            ->where('parent_id', $category->parent_id)
            ->orderBy('sort_order')->orderBy('id')
            ->get();

        $index = $siblings->search(fn (Category $c) => $c->id === $category->id);
        $targetIndex = $index + $direction;

        if ($index === false || $targetIndex < 0 || $targetIndex >= $siblings->count()) {
            return;
        }

        $target = $siblings[$targetIndex];
        [$categoryOrder, $targetOrder] = [$category->sort_order, $target->sort_order];

        $category->update(['sort_order' => $targetOrder]);
        $target->update(['sort_order' => $categoryOrder]);
    }
}
