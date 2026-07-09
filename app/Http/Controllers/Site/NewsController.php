<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\NewsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function __construct(
        private readonly NewsService $newsService,
    ) {}

    /**
     * Public news list (optionally filtered by category).
     */
    public function index(Request $request): View
    {
        $categoryId = $request->integer('kategoriya') ?: null;

        return view('pages.site.news.index', $this->newsService->index($categoryId));
    }

    /**
     * Public single news item.
     */
    public function show(string $slug): View
    {
        return view('pages.site.news.show', $this->newsService->show($slug));
    }
}
