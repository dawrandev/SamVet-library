<?php

namespace App\Repositories\Contracts;

use App\Models\SubscriptionCatalog;
use Illuminate\Support\Collection;

interface SubscriptionCatalogRepositoryInterface
{
    /**
     * All catalog entries for a year, with their journal loaded.
     */
    public function forYear(int $year): Collection;

    /**
     * Every year that has at least one catalog entry (for the year picker).
     *
     * @return list<int>
     */
    public function years(): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SubscriptionCatalog;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SubscriptionCatalog $entry, array $data): SubscriptionCatalog;

    public function delete(SubscriptionCatalog $entry): void;
}
