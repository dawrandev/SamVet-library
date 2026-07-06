<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\LookupRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Common Eloquent base for all lookup repositories (DRY).
 * Each subclass only provides its model and (if needed) extends the
 * query (e.g. eager loading, ordering).
 */
abstract class BaseLookupRepository implements LookupRepositoryInterface
{
    /**
     * The model class this repository operates on.
     *
     * @return class-string<Model>
     */
    abstract protected function model(): string;

    /**
     * Hook to enrich the list query (eager load, ordering).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Model>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Model>
     */
    protected function scopeIndex($query)
    {
        return $query->latest('id');
    }

    /**
     * @return Collection<int, Model>
     */
    public function all(): Collection
    {
        $model = $this->model();

        return $this->scopeIndex($model::query())->get();
    }

    public function find(int $id): ?Model
    {
        $model = $this->model();

        return $model::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Model
    {
        $model = $this->model();

        return $model::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Model $model, array $attributes): Model
    {
        $model->update($attributes);

        return $model;
    }

    public function delete(Model $model): void
    {
        $model->delete();
    }
}
