<?php

namespace App\Services;

use App\Data\VideoTrackData;
use App\Enums\ChunkedUploadKind;
use App\Models\Video;
use App\Models\VideoTrack;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VideoTrackService
{
    /** Protected disk (local, NOT public) — mirrors audiobooks/audio. */
    private const VIDEO_DIR = 'videos/video';

    public function __construct(
        private readonly ChunkedUploadService $chunkedUploads,
    ) {}

    public function create(Video $video, VideoTrackData $data): VideoTrack
    {
        return DB::transaction(function () use ($video, $data) {
            $attributes = $data->toAttributes();
            $attributes['video_id'] = $video->id;
            $attributes['sort_order'] = ((int) $video->tracks()->max('sort_order')) + 1;

            if ($path = $this->resolveVideoFile($data)) {
                $attributes['video_file'] = $path;
            }

            return $video->tracks()->create($attributes);
        });
    }

    public function update(VideoTrack $track, VideoTrackData $data): VideoTrack
    {
        return DB::transaction(function () use ($track, $data) {
            $attributes = $data->toAttributes();

            if ($path = $this->resolveVideoFile($data)) {
                $this->deleteFile($track->video_file);
                $attributes['video_file'] = $path;
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

    /**
     * Either a direct upload (video_file) or one assembled via chunked
     * upload (video_file_token) — never both, FormRequest guards that.
     * Returns null when neither was given (e.g. an update that didn't touch
     * the file).
     */
    private function resolveVideoFile(VideoTrackData $data): ?string
    {
        if ($data->video_file_token) {
            return $this->chunkedUploads->claimAndMove($data->video_file_token, ChunkedUploadKind::Video, Auth::guard('web')->user(), self::VIDEO_DIR);
        }

        return $data->video_file ? $this->storeProtected($data->video_file) : null;
    }

    private function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }
}
