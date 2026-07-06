<?php

namespace App\Repositories\Eloquent;

use App\Models\MenuItem;
use App\Models\Page;
use App\Repositories\Contracts\PageRepositoryInterface;

class PageRepository implements PageRepositoryInterface
{
    public function findForMenuItem(MenuItem $menuItem): ?Page
    {
        return Page::where('menu_item_id', $menuItem->id)->first();
    }

    public function updateOrCreateForMenuItem(MenuItem $menuItem, array $attributes): Page
    {
        return Page::updateOrCreate(
            ['menu_item_id' => $menuItem->id],
            $attributes,
        );
    }
}
