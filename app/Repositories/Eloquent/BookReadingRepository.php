<?php

namespace App\Repositories\Eloquent;

use App\Models\Book;
use App\Models\BookReading;
use App\Models\Reader;
use App\Repositories\Contracts\BookReadingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BookReadingRepository implements BookReadingRepositoryInterface
{
    public function log(Reader $reader, Book $book): BookReading
    {
        return BookReading::create([
            'reader_id' => $reader->id,
            'book_id' => $book->id,
            'read_at' => now(),
        ]);
    }

    public function paginateForReader(int $readerId, int $perPage = 10): LengthAwarePaginator
    {
        return BookReading::query()
            ->with('book')
            ->where('reader_id', $readerId)
            ->latest('read_at')
            ->paginate($perPage, ['*'], 'readings_page')
            ->withQueryString();
    }
}
