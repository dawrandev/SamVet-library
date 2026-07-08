<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Admin panel home page — key library metrics.
     */
    public function index(DashboardService $dashboard): View
    {
        return view('pages.admin.dashboard', $dashboard->stats());
    }
}
