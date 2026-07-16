<?php

namespace App\Services;

use App\Data\SubscriptionData;
use App\Models\Journal;
use App\Models\Reader;
use App\Models\Subscription;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptions,
    ) {}

    /**
     * Paginated, filtered list.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->subscriptions->paginate($filters, $perPage);
    }

    /**
     * Total subscription amount for the given filters (report figure).
     *
     * @param  array<string, mixed>  $filters
     */
    public function sumAmount(array $filters = []): float
    {
        return $this->subscriptions->sumAmount($filters);
    }

    /**
     * Options for the filter dropdowns and the create/edit form.
     *
     * @return array<string, mixed>
     */
    public function formOptions(): array
    {
        return [
            // affiliation_* / index — shown as a read-only reference once a reader/journal is picked.
            'readers' => Reader::orderBy('full_name')->get(['id', 'full_name', 'type', 'affiliation_place', 'affiliation_unit', 'affiliation_group']),
            'journals' => Journal::orderBy('name')->get(['id', 'name', 'kind', 'index']),
        ];
    }

    public function create(SubscriptionData $data): Subscription
    {
        return DB::transaction(function () use ($data) {
            return $this->subscriptions->create($data->toAttributes());
        });
    }

    public function update(Subscription $subscription, SubscriptionData $data): Subscription
    {
        return DB::transaction(function () use ($subscription, $data) {
            return $this->subscriptions->update($subscription, $data->toAttributes());
        });
    }

    public function delete(Subscription $subscription): void
    {
        DB::transaction(function () use ($subscription) {
            $this->subscriptions->delete($subscription);
        });
    }
}
