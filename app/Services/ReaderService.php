<?php

namespace App\Services;

use App\Data\ReaderData;
use App\Models\Reader;
use App\Repositories\Contracts\ReaderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReaderService
{
    public function __construct(
        private readonly ReaderRepositoryInterface $readers,
    ) {}

    /**
     * Paginated, filtered list.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->readers->paginate($filters, $perPage);
    }

    public function create(ReaderData $data): Reader
    {
        return DB::transaction(function () use ($data) {
            $attributes = $data->toAttributes();

            // Photo — public disk (avatar)
            if ($data->photo) {
                $attributes['photo'] = $this->storePublic($data->photo, 'reader-photos');
            }

            return $this->readers->create($attributes);
        });
    }

    public function update(Reader $reader, ReaderData $data): Reader
    {
        return DB::transaction(function () use ($reader, $data) {
            $attributes = $data->toAttributes();

            if ($data->photo) {
                $this->deleteFile('public', $reader->photo);
                $attributes['photo'] = $this->storePublic($data->photo, 'reader-photos');
            }

            return $this->readers->update($reader, $attributes);
        });
    }

    public function delete(Reader $reader): void
    {
        DB::transaction(function () use ($reader) {
            $this->deleteFile('public', $reader->photo);

            $this->readers->delete($reader);
        });
    }

    private function storePublic(UploadedFile $file, string $dir): string
    {
        return $file->store($dir, 'public');
    }

    private function deleteFile(string $disk, ?string $path): void
    {
        if ($path && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }
}
