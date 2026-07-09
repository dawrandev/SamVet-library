<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\ContentPageService;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        private readonly ContentPageService $contentPageService,
    ) {}

    /**
     * Public content page (menu item with a rich-text body).
     */
    public function show(int $id): View
    {
        return view('pages.site.page', $this->contentPageService->show($id));
    }
}
