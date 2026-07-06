<?php

namespace App\Http\Controllers\Admin;

use App\Data\PageData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePageRequest;
use App\Models\MenuItem;
use App\Services\PageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        private readonly PageService $pageService,
    ) {}

    public function edit(MenuItem $menuItem): View
    {
        return view('pages.admin.pages.edit', [
            'menuItem' => $menuItem,
            'page' => $this->pageService->forMenuItem($menuItem),
        ]);
    }

    public function update(SavePageRequest $request, MenuItem $menuItem): RedirectResponse
    {
        $this->pageService->save($menuItem, PageData::fromRequest($request));

        return redirect()
            ->route('admin.menu-items.index')
            ->with('success', __('Sahifa saqlandi.'));
    }
}
