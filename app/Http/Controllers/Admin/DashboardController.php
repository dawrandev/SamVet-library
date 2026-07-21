<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Admin panel home page — key library metrics.
     */
    public function index(Request $request, DashboardService $dashboard): View
    {
        return view('pages.admin.dashboard', $dashboard->stats(
            $request->query('from'),
            $request->query('to'),
        ));
    }
}
