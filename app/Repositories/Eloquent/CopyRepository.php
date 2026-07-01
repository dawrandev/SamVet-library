<?php

namespace App\Repositories\Eloquent;

use App\Models\BookCopy;
use App\Repositories\Contracts\CopyRepositoryInterface;

class CopyRepository implements CopyRepositoryInterface
{
    public function create(array $data): BookCopy
    {
        return BookCopy::create($data);
    }

    public function update(BookCopy $copy, array $data): BookCopy
    {
        $copy->update($data);

        return $copy;
    }

    public function delete(BookCopy $copy): void
    {
        $copy->delete();
    }
}
