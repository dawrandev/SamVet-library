<?php

namespace App\Repositories\Contracts;

use App\Models\Subscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SubscriptionRepositoryInterface
{
    /**
     * Filtered, paginated list of subscriptions (with subscriber + journal eager-loaded).
     *
     * @param  array{subscriber_id?: int, journal_id?: int, year?: int}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Total subscription amount for the given filters (report figure).
     *
     * @param  array{subscriber_id?: int, journal_id?: int, year?: int}  $filters
     */
    public function sumAmount(array $filters = []): float;

    public function find(int $id): ?Subscription;

    public function create(array $data): Subscription;

    public function update(Subscription $subscription, array $data): Subscription;

    public function delete(Subscription $subscription): void;
}
