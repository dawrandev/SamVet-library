<?php

namespace App\Repositories\Eloquent;

use App\Models\JournalCopy;
use App\Repositories\Contracts\JournalCopyRepositoryInterface;

class JournalCopyRepository implements JournalCopyRepositoryInterface
{
    public function create(array $data): JournalCopy
    {
        return JournalCopy::create($data);
    }

    public function update(JournalCopy $copy, array $data): JournalCopy
    {
        $copy->update($data);

        return $copy;
    }

    public function delete(JournalCopy $copy): void
    {
        $copy->delete();
    }
}
