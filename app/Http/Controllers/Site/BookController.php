<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\BookPageService;
use Illuminate\View\View;

class BookController extends Controller
{
    public function __construct(
        private readonly BookPageService $bookPageService,
    ) {}

    /**
     * Public book detail page (bibliographic record, no download).
     */
    public function show(string $slug): View
    {
        return view('pages.site.book', $this->bookPageService->show($slug));
    }
}
