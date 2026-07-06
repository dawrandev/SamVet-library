<?php

namespace App\Services;

use App\Data\SubscriberData;
use App\Models\Subscriber;
use App\Repositories\Contracts\SubscriberRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SubscriberService
{
    public function __construct(
        private readonly SubscriberRepositoryInterface $subscribers,
    ) {}

    /**
     * Paginated, filtered list.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->subscribers->paginate($filters, $perPage);
    }

    public function create(SubscriberData $data): Subscriber
    {
        return DB::transaction(function () use ($data) {
            return $this->subscribers->create($data->toAttributes());
        });
    }

    public function update(Subscriber $subscriber, SubscriberData $data): Subscriber
    {
        return DB::transaction(function () use ($subscriber, $data) {
            return $this->subscribers->update($subscriber, $data->toAttributes());
        });
    }

    public function delete(Subscriber $subscriber): void
    {
        DB::transaction(function () use ($subscriber) {
            $this->subscribers->delete($subscriber);
        });
    }
}
