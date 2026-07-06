<?php

namespace App\Http\Controllers\Admin;

use App\Data\NewsData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreNewsRequest;
use App\Http\Requests\Admin\UpdateNewsRequest;
use App\Models\News;
use App\Services\NewsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function __construct(
        private readonly NewsService $newsService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'news_category_id', 'status']);

        return view('pages.admin.news.index', [
            'news' => $this->newsService->paginate($filters),
            'filters' => $filters,
            ...$this->newsService->filterOptions(),
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.news.create', $this->newsService->formOptions());
    }

    public function store(StoreNewsRequest $request): RedirectResponse
    {
        $this->newsService->create(NewsData::fromRequest($request));

        return redirect()
            ->route('admin.news.index')
            ->with('success', __('Yangilik yaratildi.'));
    }

    public function edit(News $news): View
    {
        $news->load(['category', 'images']);

        return view('pages.admin.news.edit', [
            'news' => $news,
            ...$this->newsService->formOptions(),
        ]);
    }

    public function update(UpdateNewsRequest $request, News $news): RedirectResponse
    {
        $this->newsService->update($news, NewsData::fromRequest($request));

        return redirect()
            ->route('admin.news.index')
            ->with('success', __('Yangilik yangilandi.'));
    }

    public function destroy(News $news): RedirectResponse
    {
        $this->newsService->delete($news);

        return redirect()
            ->route('admin.news.index')
            ->with('success', __('Yangilik o‘chirildi.'));
    }
}
