<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\JournalPageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JournalController extends Controller
{
    public function __construct(
        private readonly JournalPageService $journalPageService,
    ) {}

    /**
     * Public journal/newspaper detail with its issues and articles.
     */
    public function show(Request $request, string $slug): View
    {
        $issueId = $request->integer('son') ?: null;

        return view('pages.site.journal', $this->journalPageService->show($slug, $issueId));
    }
}
