<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookReading;
use App\Models\Reader;
use App\Repositories\Contracts\BookReadingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BookReadingService
{
    public function __construct(
        private readonly BookReadingRepositoryInterface $readings,
    ) {}

    public function log(Reader $reader, Book $book): BookReading
    {
        return $this->readings->log($reader, $book);
    }

    public function paginateForReader(int $readerId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->readings->paginateForReader($readerId, $perPage);
    }
}
