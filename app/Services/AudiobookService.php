<?php

namespace App\Services;

use App\Data\AudiobookData;
use App\Models\Audiobook;
use App\Repositories\Contracts\AudiobookRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AudiobookService
{
    private const COVERS_DIR = 'audiobook-covers';

    public function __construct(
        private readonly AudiobookRepositoryInterface $audiobooks,
        private readonly AudioTrackService $tracks,
    ) {}

    /**
     * Paginated, filtered list.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->audiobooks->paginate($filters, $perPage);
    }

    public function create(AudiobookData $data): Audiobook
    {
        return DB::transaction(function () use ($data) {
            $attributes = $data->toAttributes();

            if ($data->cover) {
                $attributes['cover_image'] = $this->storePublic($data->cover);
            }

            return $this->audiobooks->create($attributes); // slug — Observer
        });
    }

    public function update(Audiobook $audiobook, AudiobookData $data): Audiobook
    {
        return DB::transaction(function () use ($audiobook, $data) {
            $attributes = $data->toAttributes();

            if ($data->cover) {
                $this->deleteFile('public', $audiobook->cover_image);
                $attributes['cover_image'] = $this->storePublic($data->cover);
            }

            return $this->audiobooks->update($audiobook, $attributes);
        });
    }

    public function delete(Audiobook $audiobook): void
    {
        DB::transaction(function () use ($audiobook) {
            $this->deleteFile('public', $audiobook->cover_image);

            // Track rows cascade-delete in the DB, but their protected files
            // on disk don't — those have to be removed explicitly.
            foreach ($audiobook->tracks as $track) {
                $this->tracks->deleteFileOnly($track);
            }

            $this->audiobooks->delete($audiobook);
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
