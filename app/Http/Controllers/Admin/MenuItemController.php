<?php

namespace App\Http\Controllers\Admin;

use App\Data\MenuItemData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMenuItemRequest;
use App\Http\Requests\Admin\UpdateMenuItemRequest;
use App\Models\MenuItem;
use App\Services\MenuItemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuItemController extends Controller
{
    public function __construct(
        private readonly MenuItemService $menuItemService,
    ) {}

    public function index(): View
    {
        return view('pages.admin.menu-items.index', [
            'tree' => $this->menuItemService->tree(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('pages.admin.menu-items.create', [
            'parents' => $this->menuItemService->parentOptions(),
            'selectedParentId' => $request->integer('parent') ?: null,
        ]);
    }

    public function store(StoreMenuItemRequest $request): RedirectResponse
    {
        $this->menuItemService->create(MenuItemData::fromRequest($request));

        return redirect()
            ->route('admin.menu-items.index')
            ->with('success', __('Menyu elementi yaratildi.'));
    }

    public function edit(MenuItem $menuItem): View
    {
        return view('pages.admin.menu-items.edit', [
            'menuItem' => $menuItem,
            'parents' => $this->menuItemService->parentOptions($menuItem),
        ]);
    }

    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem): RedirectResponse
    {
        $this->menuItemService->update($menuItem, MenuItemData::fromRequest($request));

        return redirect()
            ->route('admin.menu-items.index')
            ->with('success', __('Menyu elementi yangilandi.'));
    }

    public function destroy(MenuItem $menuItem): RedirectResponse
    {
        $this->menuItemService->delete($menuItem);

        return redirect()
            ->route('admin.menu-items.index')
            ->with('success', __('Menyu elementi o‘chirildi.'));
    }
}
