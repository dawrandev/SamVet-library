<?php

namespace App\Repositories\Contracts;

use App\Models\JournalCopy;

interface JournalCopyRepositoryInterface
{
    public function create(array $data): JournalCopy;

    public function update(JournalCopy $copy, array $data): JournalCopy;

    public function delete(JournalCopy $copy): void;
}
