<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\SectionService;
use Illuminate\View\View;

class SectionController extends Controller
{
    public function __construct(
        private readonly SectionService $sections,
    ) {}

    /**
     * All sections of the fund (book types and periodicals).
     */
    public function index(): View
    {
        return view('pages.site.sections', ['tiles' => $this->sections->tiles()]);
    }
}
