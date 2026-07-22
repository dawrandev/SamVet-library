<?php

namespace App\Services;

use App\Data\VideoData;
use App\Models\Video;
use App\Repositories\Contracts\VideoRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VideoService
{
    private const COVERS_DIR = 'video-covers';

    public function __construct(
        private readonly VideoRepositoryInterface $videos,
        private readonly VideoTrackService $tracks,
    ) {}

    /**
     * Paginated, filtered list.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->videos->paginate($filters, $perPage);
    }

    public function create(VideoData $data): Video
    {
        return DB::transaction(function () use ($data) {
            $attributes = $data->toAttributes();

            if ($data->cover) {
                $attributes['cover_image'] = $this->storePublic($data->cover);
            }

            return $this->videos->create($attributes); // slug — Observer
        });
    }

    public function update(Video $video, VideoData $data): Video
    {
        return DB::transaction(function () use ($video, $data) {
            $attributes = $data->toAttributes();

            if ($data->cover) {
                $this->deleteFile('public', $video->cover_image);
                $attributes['cover_image'] = $this->storePublic($data->cover);
            }

            return $this->videos->update($video, $attributes);
        });
    }

    public function delete(Video $video): void
    {
        DB::transaction(function () use ($video) {
            $this->deleteFile('public', $video->cover_image);

            // Track rows cascade-delete in the DB, but their protected files
            // on disk don't — those have to be removed explicitly.
            foreach ($video->tracks as $track) {
                $this->tracks->deleteFileOnly($track);
            }

            $this->videos->delete($video);
        });
    }

    private function storePublic(UploadedFile $file): string
    {
        return $file->store(self::COVERS_DIR, 'public');
    }

    private function deleteFile(string $disk, ?string $path): void
    {
        if ($path && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }
}
