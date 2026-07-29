<?php

namespace App\Services;

use App\Data\SubscriptionCatalogData;
use App\Models\SubscriptionCatalog;
use App\Repositories\Contracts\SubscriptionCatalogRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SubscriptionCatalogService
{
    public function __construct(
        private readonly SubscriptionCatalogRepositoryInterface $catalog,
    ) {}

    public function forYear(int $year): Collection
    {
        return $this->catalog->forYear($year);
    }

    /**
     * @return list<int>
     */
    public function years(): array
    {
        return $this->catalog->years();
    }

    public function create(SubscriptionCatalogData $data): SubscriptionCatalog
    {
        return DB::transaction(fn () => $this->catalog->create($data->toAttributes()));
    }

    public function update(SubscriptionCatalog $entry, SubscriptionCatalogData $data): SubscriptionCatalog
    {
        return DB::transaction(fn () => $this->catalog->update($entry, $data->toAttributes()));
    }

    public function delete(SubscriptionCatalog $entry): void
    {
        DB::transaction(fn () => $this->catalog->delete($entry));
    }
}
