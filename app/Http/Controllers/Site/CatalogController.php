<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\CatalogFilterRequest;
use App\Http\Resources\CatalogSearchResultResource;
use App\Services\Site\CatalogService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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

    /**
     * Live typeahead for the homepage/catalog search bar — top matches
     * across all five resource types as the visitor types.
     */
    public function quickSearch(Request $request): AnonymousResourceCollection
    {
        $term = $request->string('q')->trim()->toString();

        return CatalogSearchResultResource::collection($this->catalogService->quickSearch($term));
    }
}
