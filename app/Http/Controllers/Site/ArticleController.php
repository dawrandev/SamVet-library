<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\ArticlePageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticlePageService $articlePageService,
    ) {}

    /**
     * Public article catalog.
     *
     * Articles were the one catalogued resource with no listing of its own:
     * a journal's articles are reachable through the issue, but an external
     * one (no journal_issue_id) had no page linking to it at all, so nine
     * uploaded articles sat on the site unreachable except by direct URL.
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['search']);

        return view('pages.site.articles', [
            'articles' => $this->articlePageService->index($filters),
            'filters' => $filters,
        ]);
    }

    /**
     * Public article (maqola) detail page (bibliographic record, no download).
     */
    public function show(string $slug): View
    {
        return view('pages.site.article', $this->articlePageService->show($slug));
    }
}
