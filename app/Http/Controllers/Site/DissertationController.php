<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\DissertationPageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DissertationController extends Controller
{
    public function __construct(
        private readonly DissertationPageService $dissertationPageService,
    ) {}

    /**
     * Public dissertation catalog.
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'resource_field_id']);

        return view('pages.site.dissertations', [
            'dissertations' => $this->dissertationPageService->index($filters),
            'filters' => $filters,
        ]);
    }

    /**
     * Public dissertation detail page (bibliographic record, no download).
     */
    public function show(string $slug): View
    {
        return view('pages.site.dissertation', $this->dissertationPageService->show($slug));
    }
}
