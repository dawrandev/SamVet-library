<?php

namespace App\Repositories\Contracts;

use App\Models\BookCopy;

interface CopyRepositoryInterface
{
    public function create(array $data): BookCopy;

    public function update(BookCopy $copy, array $data): BookCopy;

    public function delete(BookCopy $copy): void;
}
