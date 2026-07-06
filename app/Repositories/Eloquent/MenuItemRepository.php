<?php

namespace App\Repositories\Eloquent;

use App\Models\MenuItem;
use App\Repositories\Contracts\MenuItemRepositoryInterface;
use Illuminate\Support\Collection;

class MenuItemRepository implements MenuItemRepositoryInterface
{
    /**
     * Arbitrary-depth tree: the `children` relation loads itself recursively
     * (children.children...). Sufficient for several levels.
     */
    public function treeForAdmin(): Collection
    {
        return MenuItem::query()
            ->whereNull('parent_id')
            ->with('children.children.children.children.children')
            ->orderBy('sort_order')
            ->get();
    }

    public function find(int $id): ?MenuItem
    {
        return MenuItem::find($id);
    }

    public function create(array $data): MenuItem
    {
        return MenuItem::create($data);
    }

    public function update(MenuItem $menuItem, array $data): MenuItem
    {
        $menuItem->update($data);

        return $menuItem;
    }

    public function delete(MenuItem $menuItem): void
    {
        $menuItem->delete();
    }
}
