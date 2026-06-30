<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Admin panel bosh sahifasi.
     */
    public function index(): View
    {
        return view('pages.admin.dashboard');
    }
}
