<?php

namespace App\Http\Controllers\Admin;

use App\Data\VideoData;
use App\Exports\VideosExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVideoRequest;
use App\Http\Requests\Admin\UpdateVideoRequest;
use App\Models\Video;
use App\Services\VideoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VideoController extends Controller
{
    public function __construct(
        private readonly VideoService $videoService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search']);

        return view('pages.admin.videos.index', [
            'videos' => $this->videoService->paginate($filters),
            'filters' => $filters,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = array_filter($request->only(['search']), fn ($v) => $v !== null && $v !== '');

        return Excel::download(new VideosExport($filters), 'videolar-'.now()->format('Y-m-d').'.xlsx');
    }

    public function show(Video $video): View
    {
        $video->load('tracks');

        return view('pages.admin.videos.show', ['video' => $video]);
    }

    public function create(): View
    {
        return view('pages.admin.videos.create');
    }

    public function store(StoreVideoRequest $request): RedirectResponse
    {
        $video = $this->videoService->create(VideoData::fromRequest($request));

        return redirect()
            ->route('admin.videos.show', $video)
            ->with('success', __('Video yaratildi. Endi videolarni qo‘shishingiz mumkin.'));
    }

    public function edit(Video $video): View
    {
        return view('pages.admin.videos.edit', ['video' => $video]);
    }

    public function update(UpdateVideoRequest $request, Video $video): RedirectResponse
    {
        $this->videoService->update($video, VideoData::fromRequest($request));

        return redirect()
            ->route('admin.videos.index')
            ->with('success', __('Video yangilandi.'));
    }

    public function destroy(Video $video): RedirectResponse
    {
        $this->videoService->delete($video);

        return redirect()
            ->route('admin.videos.index')
            ->with('success', __('Video o‘chirildi.'));
    }
}
