<?php

namespace App\Http\Controllers\Admin;

use App\Data\VideoTrackData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVideoTrackRequest;
use App\Http\Requests\Admin\UpdateVideoTrackRequest;
use App\Models\Video;
use App\Models\VideoTrack;
use App\Services\VideoTrackService;
use Illuminate\Http\RedirectResponse;

class VideoTrackController extends Controller
{
    public function __construct(
        private readonly VideoTrackService $trackService,
    ) {}

    public function store(StoreVideoTrackRequest $request, Video $video): RedirectResponse
    {
        $this->trackService->create($video, VideoTrackData::fromRequest($request));

        return redirect()
            ->route('admin.videos.show', $video)
            ->with('success', __('Video qo‘shildi.'));
    }

    public function update(UpdateVideoTrackRequest $request, Video $video, VideoTrack $track): RedirectResponse
    {
        $this->ensureTrackBelongsToVideo($video, $track);

        $this->trackService->update($track, VideoTrackData::fromRequest($request));

        return redirect()
            ->route('admin.videos.show', $video)
            ->with('success', __('Video yangilandi.'));
    }

    public function destroy(Video $video, VideoTrack $track): RedirectResponse
    {
        $this->ensureTrackBelongsToVideo($video, $track);

        $this->trackService->delete($track);

        return redirect()
            ->route('admin.videos.show', $video)
            ->with('success', __('Video o‘chirildi.'));
    }

    /**
     * Security: the track must belong to this specific video.
     */
    private function ensureTrackBelongsToVideo(Video $video, VideoTrack $track): void
    {
        abort_unless($track->video_id === $video->id, 404);
    }
}
