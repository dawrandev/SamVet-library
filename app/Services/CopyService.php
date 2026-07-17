<?php

namespace App\Services;

use App\Data\CopyData;
use App\Models\Book;
use App\Models\BookCopy;
use App\Repositories\Contracts\CopyRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CopyService
{
    public function __construct(
        private readonly CopyRepositoryInterface $copies,
    ) {}

    public function create(Book $book, CopyData $data): BookCopy
    {
        return DB::transaction(function () use ($book, $data) {
            $attributes = $data->toAttributes();
            $attributes['book_id'] = $book->id;

            return $this->copies->create($attributes);
        });
    }

    public function update(BookCopy $copy, CopyData $data): BookCopy
    {
        return DB::transaction(fn () => $this->copies->update($copy, $data->toAttributes()));
    }

    public function delete(BookCopy $copy): void
    {
        DB::transaction(fn () => $this->copies->delete($copy));
    }
}
