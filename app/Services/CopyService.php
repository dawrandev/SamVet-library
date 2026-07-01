<?php

namespace App\Services;

use App\Data\CopyData;
use App\Models\Book;
use App\Models\BookCopy;
use App\Repositories\Contracts\CopyRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CopyService
{
    /**
     * Aktlar (kirish/chiqish) — himoyalangan PDF fayllar papkasi.
     */
    private const ACTS_DIR = 'book-copies/acts';

    public function __construct(
        private readonly CopyRepositoryInterface $copies,
    ) {}

    public function create(Book $book, CopyData $data): BookCopy
    {
        return DB::transaction(function () use ($book, $data) {
            $attributes = $data->toAttributes();
            $attributes['book_id'] = $book->id;

            // Aktlar — himoyalangan (local disk, public EMAS)
            if ($data->acquisition_act) {
                $attributes['acquisition_act'] = $this->storeProtected($data->acquisition_act);
            }
            if ($data->disposal_act) {
                $attributes['disposal_act'] = $this->storeProtected($data->disposal_act);
            }

            return $this->copies->create($attributes);
        });
    }

    public function update(BookCopy $copy, CopyData $data): BookCopy
    {
        return DB::transaction(function () use ($copy, $data) {
            $attributes = $data->toAttributes();

            if ($data->acquisition_act) {
                $this->deleteFile($copy->acquisition_act);
                $attributes['acquisition_act'] = $this->storeProtected($data->acquisition_act);
            }
            if ($data->disposal_act) {
                $this->deleteFile($copy->disposal_act);
                $attributes['disposal_act'] = $this->storeProtected($data->disposal_act);
            }

            return $this->copies->update($copy, $attributes);
        });
    }

    public function delete(BookCopy $copy): void
    {
        DB::transaction(function () use ($copy) {
            $this->deleteFile($copy->acquisition_act);
            $this->deleteFile($copy->disposal_act);

            $this->copies->delete($copy);
        });
    }

    private function storeProtected(UploadedFile $file): string
    {
        return $file->store(self::ACTS_DIR, 'local');
    }

    private function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }
}
