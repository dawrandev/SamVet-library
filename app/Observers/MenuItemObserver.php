<?php

namespace App\Observers;

use App\Models\MenuItem;

class MenuItemObserver
{
    /**
     * When a new item is created, the sort order is assigned automatically:
     * if empty/0, it is set to max(sort_order)+1 within the same parent.
     */
    public function creating(MenuItem $menuItem): void
    {
        if (empty($menuItem->sort_order)) {
            $max = MenuItem::query()
                ->where('parent_id', $menuItem->parent_id)
                ->max('sort_order');

            $menuItem->sort_order = ((int) $max) + 1;
        }
    }
}
