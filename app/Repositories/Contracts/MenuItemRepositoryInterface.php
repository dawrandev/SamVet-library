<?php

namespace App\Repositories\Contracts;

use App\Models\MenuItem;
use Illuminate\Support\Collection;

interface MenuItemRepositoryInterface
{
    /**
     * Admin daraxti: barcha ildiz elementlar, bolalari rekursiv eager yuklangan,
     * sort_order bo'yicha tartiblangan.
     *
     * @return Collection<int, MenuItem>
     */
    public function treeForAdmin(): Collection;

    public function find(int $id): ?MenuItem;

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
