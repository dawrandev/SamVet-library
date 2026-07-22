<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\VideoPageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VideoController extends Controller
{
    public function __construct(
        private readonly VideoPageService $videoPageService,
    ) {}

    /**
     * Public video catalog.
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['search']);

        return view('pages.site.videos', [
            'videos' => $this->videoPageService->index($filters),
            'filters' => $filters,
        ]);
    }

    /**
     * Public video detail page (metadata + track list, no playback).
     */
    public function show(string $slug): View
    {
        return view('pages.site.video', $this->videoPageService->show($slug));
    }
}
