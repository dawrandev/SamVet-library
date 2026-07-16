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

    /**
     * The public navbar tree: every active top-level item with its active
     * children loaded recursively (arbitrary depth). Drives the site header
     * dropdowns and their nested submenus.
     */
    public function publicTree(): Collection
    {
        return MenuItem::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with('activeChildrenRecursive')
            ->orderBy('sort_order')
            ->get();
    }

    public function find(int $id): ?MenuItem
    {
        return MenuItem::find($id);
    }

    public function primarySection(): ?MenuItem
    {
        return MenuItem::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->whereHas('children', fn ($q) => $q->where('is_active', true))
            ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->first();
    }

    public function findPublicPage(int $id): ?MenuItem
    {
        return MenuItem::query()
            ->where('is_active', true)
            ->with(['page.images', 'parent'])
            ->find($id);
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
