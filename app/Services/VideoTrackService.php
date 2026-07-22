<?php

namespace App\Services;

use App\Data\VideoTrackData;
use App\Models\Video;
use App\Models\VideoTrack;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VideoTrackService
{
    /** Protected disk (local, NOT public) — mirrors audiobooks/audio. */
    private const VIDEO_DIR = 'videos/video';

    public function create(Video $video, VideoTrackData $data): VideoTrack
    {
        return DB::transaction(function () use ($video, $data) {
            $attributes = $data->toAttributes();
            $attributes['video_id'] = $video->id;
            $attributes['sort_order'] = ((int) $video->tracks()->max('sort_order')) + 1;

            if ($data->video_file) {
                $attributes['video_file'] = $this->storeProtected($data->video_file);
            }

            return $video->tracks()->create($attributes);
        });
    }

    public function update(VideoTrack $track, VideoTrackData $data): VideoTrack
    {
        return DB::transaction(function () use ($track, $data) {
            $attributes = $data->toAttributes();

            if ($data->video_file) {
                $this->deleteFile($track->video_file);
                $attributes['video_file'] = $this->storeProtected($data->video_file);
            }

            $track->update($attributes);

            return $track;
        });
    }

    public function delete(VideoTrack $track): void
    {
        DB::transaction(function () use ($track) {
            $this->deleteFile($track->video_file);
            $track->delete();
        });
    }

    /** Removes only the stored file — used when the parent Video itself is being deleted (row cascades in DB). */
    public function deleteFileOnly(VideoTrack $track): void
    {
        $this->deleteFile($track->video_file);
    }

    private function storeProtected(UploadedFile $file): string
    {
        return $file->store(self::VIDEO_DIR, 'local');
    }

    private function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }
}
