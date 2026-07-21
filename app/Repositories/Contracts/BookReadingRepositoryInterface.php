<?php

namespace App\Repositories\Contracts;

use App\Models\Book;
use App\Models\BookReading;
use App\Models\Reader;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BookReadingRepositoryInterface
{
    public function log(Reader $reader, Book $book): BookReading;

    /** One reader's online-reading history, most recent first. */
    public function paginateForReader(int $readerId, int $perPage = 10): LengthAwarePaginator;
}
