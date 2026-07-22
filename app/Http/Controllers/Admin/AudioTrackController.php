<?php

namespace App\Http\Controllers\Admin;

use App\Data\AudioTrackData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAudioTrackRequest;
use App\Http\Requests\Admin\UpdateAudioTrackRequest;
use App\Models\Audiobook;
use App\Models\AudioTrack;
use App\Services\AudioTrackService;
use Illuminate\Http\RedirectResponse;

class AudioTrackController extends Controller
{
    public function __construct(
        private readonly AudioTrackService $trackService,
    ) {}

    public function store(StoreAudioTrackRequest $request, Audiobook $audiobook): RedirectResponse
    {
        $this->trackService->create($audiobook, AudioTrackData::fromRequest($request));

        return redirect()
            ->route('admin.audiobooks.show', $audiobook)
            ->with('success', __('Audio qo‘shildi.'));
    }

    public function update(UpdateAudioTrackRequest $request, Audiobook $audiobook, AudioTrack $track): RedirectResponse
    {
        $this->ensureTrackBelongsToAudiobook($audiobook, $track);

        $this->trackService->update($track, AudioTrackData::fromRequest($request));

        return redirect()
            ->route('admin.audiobooks.show', $audiobook)
            ->with('success', __('Audio yangilandi.'));
    }

    public function destroy(Audiobook $audiobook, AudioTrack $track): RedirectResponse
    {
        $this->ensureTrackBelongsToAudiobook($audiobook, $track);

        $this->trackService->delete($track);

        return redirect()
            ->route('admin.audiobooks.show', $audiobook)
            ->with('success', __('Audio o‘chirildi.'));
    }

    /**
     * Security: the track must belong to this specific audiobook.
     */
    private function ensureTrackBelongsToAudiobook(Audiobook $audiobook, AudioTrack $track): void
    {
        abort_unless($track->audiobook_id === $audiobook->id, 404);
    }
}
