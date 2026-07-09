<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\ArticlePageService;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticlePageService $articlePageService,
    ) {}

    /**
     * Public article (maqola) detail page (bibliographic record, no download).
     */
    public function show(string $slug): View
    {
        return view('pages.site.article', $this->articlePageService->show($slug));
    }
}
