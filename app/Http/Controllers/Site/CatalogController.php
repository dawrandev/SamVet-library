<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\CatalogFilterRequest;
use App\Services\Site\CatalogService;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalogService,
    ) {}

    /**
     * Public electronic catalog: filterable, paginated list of books.
     */
    public function index(CatalogFilterRequest $request): View
    {
        return view('pages.site.catalog', $this->catalogService->catalogData($request->filters()));
    }
}
