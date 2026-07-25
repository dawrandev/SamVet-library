<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\ComputerAvailabilityService;
use Illuminate\View\View;

class ComputerAvailabilityController extends Controller
{
    public function __construct(
        private readonly ComputerAvailabilityService $computerAvailability,
    ) {}

    /**
     * Reader-only: which computers are free right now, by room.
     */
    public function index(): View
    {
        return view('pages.site.computers', ['rooms' => $this->computerAvailability->rooms()]);
    }
}
