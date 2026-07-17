<?php

namespace App\Repositories\Contracts;

use App\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EventRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Event;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Event $event, array $data): Event;

    public function delete(Event $event): void;
}
