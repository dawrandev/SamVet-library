<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\AudiobookPageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AudiobookController extends Controller
{
    public function __construct(
        private readonly AudiobookPageService $audiobookPageService,
    ) {}

    /**
     * Public audiobook catalog.
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['search']);

        return view('pages.site.audiobooks', [
            'audiobooks' => $this->audiobookPageService->index($filters),
            'filters' => $filters,
        ]);
    }

    /**
     * Public audiobook detail page (metadata + track list, no playback).
     */
    public function show(string $slug): View
    {
        return view('pages.site.audiobook', $this->audiobookPageService->show($slug));
    }
}
