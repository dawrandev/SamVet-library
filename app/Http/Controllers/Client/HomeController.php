<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\HomeService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly HomeService $homeService,
    ) {}

    /**
     * Public home page of the library portal.
     */
    public function index(): View
    {
        return view('pages.client.home', $this->homeService->homeData());
    }
}
