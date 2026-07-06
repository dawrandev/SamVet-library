<?php

namespace App\Repositories\Contracts;

use App\Models\Subscriber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface SubscriberRepositoryInterface
{
    /**
     * Filtered, paginated list of subscribers.
     *
     * @param  array{search?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * All subscribers (for select dropdowns).
     *
     * @return Collection<int, Subscriber>
     */
    public function all(): Collection;

    public function find(int $id): ?Subscriber;

    public function create(array $data): Subscriber;

    public function update(Subscriber $subscriber, array $data): Subscriber;

    public function delete(Subscriber $subscriber): void;
}
