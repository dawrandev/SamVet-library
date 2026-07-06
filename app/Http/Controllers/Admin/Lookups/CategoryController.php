<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\LookupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\CategoryLookupRequest;
use App\Models\Category;
use App\Services\Lookups\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $service,
    ) {}

    public function index(): View
    {
        $categories = $this->service->list();

        return view('pages.admin.lookups.categories.index', [
            'categories' => $categories,
            // For the parent category select (the check that it cannot be its own parent is done in the blade)
            'parents' => $categories,
        ]);
    }

    public function store(CategoryLookupRequest $request): RedirectResponse
    {
        $this->service->create(LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.categories.index')
            ->with('success', __('Kategoriya qo‘shildi.'));
    }

    public function update(CategoryLookupRequest $request, Category $category): RedirectResponse
    {
        $this->service->update($category, LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.categories.index')
            ->with('success', __('Kategoriya yangilandi.'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->service->delete($category);

        return redirect()
            ->route('admin.lookups.categories.index')
            ->with('success', __('Kategoriya o‘chirildi.'));
    }
}
