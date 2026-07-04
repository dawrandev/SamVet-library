<?php

namespace App\Observers;

use App\Models\MenuItem;

class MenuItemObserver
{
    /**
     * Yangi element yaratilganda tartib raqami avtomatik beriladi:
     * agar bo'sh/0 bo'lsa, o'sha ota ichidagi max(sort_order)+1 qilinadi.
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
