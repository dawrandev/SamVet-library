<?php

namespace App\Repositories\Eloquent;

use App\Models\AvtoreferatCopy;
use App\Repositories\Contracts\AvtoreferatCopyRepositoryInterface;

class AvtoreferatCopyRepository implements AvtoreferatCopyRepositoryInterface
{
    public function create(array $data): AvtoreferatCopy
    {
        return AvtoreferatCopy::create($data);
    }

    public function update(AvtoreferatCopy $copy, array $data): AvtoreferatCopy
    {
        $copy->update($data);

        return $copy;
    }

    public function delete(AvtoreferatCopy $copy): void
    {
        $copy->delete();
    }
}
