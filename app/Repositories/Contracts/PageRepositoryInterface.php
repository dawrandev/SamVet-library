<?php

namespace App\Repositories\Contracts;

use App\Models\MenuItem;
use App\Models\Page;

interface PageRepositoryInterface
{
    /**
     * The page linked to a menu item (or null).
     */
    public function findForMenuItem(MenuItem $menuItem): ?Page;

    /**
     * Creates or updates the page linked to a menu item.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateOrCreateForMenuItem(MenuItem $menuItem, array $attributes): Page;
}
