<?php

namespace App\Repositories\Contracts;

use App\Models\AvtoreferatCopy;

interface AvtoreferatCopyRepositoryInterface
{
    public function create(array $data): AvtoreferatCopy;

    public function update(AvtoreferatCopy $copy, array $data): AvtoreferatCopy;

    public function delete(AvtoreferatCopy $copy): void;
}
