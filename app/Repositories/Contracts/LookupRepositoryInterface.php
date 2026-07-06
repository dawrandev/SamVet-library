<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Common repository contract for lookups (reference data).
 * All DB queries live only in this layer.
 */
interface LookupRepositoryInterface
{
    /**
     * List (for the index page).
     *
     * @return Collection<int, Model>
     */
    public function all(): Collection;

    public function find(int $id): ?Model;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Model;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Model $model, array $attributes): Model;

    public function delete(Model $model): void;
}
