<?php

namespace App\Services;

use App\Data\MenuItemData;
use App\Models\MenuItem;
use App\Repositories\Contracts\MenuItemRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MenuItemService
{
    public function __construct(
        private readonly MenuItemRepositoryInterface $menuItems,
    ) {}

    /**
     * Admin tree (roots + recursive children).
     *
     * @return Collection<int, MenuItem>
     */
    public function tree(): Collection
    {
        return $this->menuItems->treeForAdmin();
    }

    public function create(MenuItemData $data): MenuItem
    {
        return $this->menuItems->create($data->toAttributes());
    }

    public function update(MenuItem $menuItem, MenuItemData $data): MenuItem
    {
        $this->guardAgainstCycle($menuItem, $data->parent_id);

        return $this->menuItems->update($menuItem, $data->toAttributes());
    }

    public function delete(MenuItem $menuItem): void
    {
        // Children are deleted at the DB level via cascade.
        $this->menuItems->delete($menuItem);
    }

    /**
     * Hierarchical list for choosing a parent menu (indented by depth).
     * When editing, the item itself and its descendants are excluded (to avoid a cycle).
     *
     * @return array<int, array{id: int, label: string}>
     */
    public function parentOptions(?MenuItem $except = null): array
    {
        $excludeIds = [];

        if ($except !== null) {
            $except->load('children');
            $excludeIds = array_merge([$except->id], $this->descendantIds($except));
        }

        $options = [];
        $this->flatten($this->tree(), $excludeIds, 0, $options);

        return $options;
    }

    /**
     * Flattens the tree into a flat list with a "— › —" prefix.
     *
     * @param  Collection<int, MenuItem>  $items
     * @param  array<int, int>  $excludeIds
     * @param  array<int, array{id: int, label: string}>  $out
     */
    private function flatten(Collection $items, array $excludeIds, int $depth, array &$out): void
    {
        foreach ($items as $item) {
            if (in_array($item->id, $excludeIds, true)) {
                continue;
            }

            $prefix = $depth > 0 ? str_repeat('— ', $depth) : '';
            $out[] = [
                'id' => $item->id,
                'label' => $prefix.$item->getTranslation('title', 'uz'),
            ];

            $this->flatten($item->children, $excludeIds, $depth + 1, $out);
        }
    }

    /**
     * Cycle guard (Service level): an item cannot be made a parent of itself or its descendant.
     */
    private function guardAgainstCycle(MenuItem $menuItem, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if ($parentId === $menuItem->id || in_array($parentId, $this->descendantIds($menuItem), true)) {
            throw ValidationException::withMessages([
                'parent_id' => __('Menyu o‘zini yoki o‘z ostidagi elementni ota sifatida tanlay olmaydi.'),
            ]);
        }
    }

    /**
     * All descendant IDs under the given item.
     *
     * @return array<int, int>
     */
    private function descendantIds(MenuItem $menuItem): array
    {
        $ids = [];

        foreach ($menuItem->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->descendantIds($child));
        }

        return $ids;
    }
}
