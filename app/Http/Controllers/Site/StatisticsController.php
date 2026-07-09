<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\StatisticsService;
use Illuminate\View\View;

class StatisticsController extends Controller
{
    public function __construct(
        private readonly StatisticsService $statisticsService,
    ) {}

    /**
     * Public statistics of the information resource center.
     */
    public function index(): View
    {
        return view('pages.site.statistics', $this->statisticsService->statisticsData());
    }
}
