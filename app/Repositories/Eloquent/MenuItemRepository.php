<?php

namespace App\Repositories\Eloquent;

use App\Models\MenuItem;
use App\Repositories\Contracts\MenuItemRepositoryInterface;
use Illuminate\Support\Collection;

class MenuItemRepository implements MenuItemRepositoryInterface
{
    /**
     * Ixtiyoriy chuqurlikdagi daraxt: `children` relation'i o'zini rekursiv
     * yuklaydi (children.children...). Bir nechta daraja uchun yetarli.
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
