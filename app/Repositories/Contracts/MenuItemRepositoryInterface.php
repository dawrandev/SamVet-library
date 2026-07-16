<?php

namespace App\Repositories\Contracts;

use App\Models\MenuItem;
use Illuminate\Support\Collection;

interface MenuItemRepositoryInterface
{
    /**
     * Admin tree: all root items, with children eager loaded recursively,
     * ordered by sort_order.
     *
     * @return Collection<int, MenuItem>
     */
    public function treeForAdmin(): Collection;

    public function find(int $id): ?MenuItem;

    /* ----- Public site reads ----- */

    /** The first active top-level section that has active children (for the nav). */
    public function primarySection(): ?MenuItem;

    /**
     * The public navbar tree: active top-level items, each with its active
     * children (one level) loaded and ordered.
     *
     * @return Collection<int, MenuItem>
     */
    public function publicTree(): Collection;

    /** An active menu item with its page and parent loaded, or null. */
    public function findPublicPage(int $id): ?MenuItem;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MenuItem;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(MenuItem $menuItem, array $data): MenuItem;

    public function delete(MenuItem $menuItem): void;
}
