<?php

namespace App\Repositories\Contracts;

use App\Models\ReaderEvent;

interface EventRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ReaderEvent;

    public function delete(ReaderEvent $event): void;
}
