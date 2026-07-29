<?php

namespace App\Repositories\Eloquent;

use App\Models\SubscriptionCatalog;
use App\Repositories\Contracts\SubscriptionCatalogRepositoryInterface;
use Illuminate\Support\Collection;

class SubscriptionCatalogRepository implements SubscriptionCatalogRepositoryInterface
{
    public function forYear(int $year): Collection
    {
        return SubscriptionCatalog::with('journal')
            ->where('year', $year)
            ->get()
            ->sortBy(fn (SubscriptionCatalog $entry) => $entry->journal?->name)
            ->values();
    }

    public function years(): array
    {
        return SubscriptionCatalog::query()
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->all();
    }

    public function create(array $data): SubscriptionCatalog
    {
        return SubscriptionCatalog::create($data);
    }

    public function update(SubscriptionCatalog $entry, array $data): SubscriptionCatalog
    {
        $entry->update($data);

        return $entry;
    }

    public function delete(SubscriptionCatalog $entry): void
    {
        $entry->delete();
    }
}
