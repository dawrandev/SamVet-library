<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\LookupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\TranslatableLookupRequest;
use App\Models\NewsCategory;
use App\Services\Lookups\NewsCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NewsCategoryController extends Controller
{
    public function __construct(
        private readonly NewsCategoryService $service,
    ) {}

    public function index(): View
    {
        return view('pages.admin.lookups.news-categories.index', [
            'newsCategories' => $this->service->list(),
        ]);
    }

    public function store(TranslatableLookupRequest $request): RedirectResponse
    {
        $this->service->create(LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.news-categories.index')
            ->with('success', __('Yangilik kategoriyasi qo‘shildi.'));
    }

    public function update(TranslatableLookupRequest $request, NewsCategory $newsCategory): RedirectResponse
    {
        $this->service->update($newsCategory, LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.news-categories.index')
            ->with('success', __('Yangilik kategoriyasi yangilandi.'));
    }

    public function destroy(NewsCategory $newsCategory): RedirectResponse
    {
        $this->service->delete($newsCategory);

        return redirect()
            ->route('admin.lookups.news-categories.index')
            ->with('success', __('Yangilik kategoriyasi o‘chirildi.'));
    }
}
