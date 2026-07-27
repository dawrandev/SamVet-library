<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\AvtoreferatPageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AvtoreferatController extends Controller
{
    public function __construct(
        private readonly AvtoreferatPageService $avtoreferatPageService,
    ) {}

    /**
     * Public avtoreferat catalog.
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['search']);

        return view('pages.site.avtoreferats', [
            'avtoreferats' => $this->avtoreferatPageService->index($filters),
            'filters' => $filters,
        ]);
    }

    /**
     * Public avtoreferat detail page (bibliographic record, no download).
     */
    public function show(string $slug): View
    {
        return view('pages.site.avtoreferat', $this->avtoreferatPageService->show($slug));
    }
}
