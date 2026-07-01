<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\LookupRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Barcha lookup repozitoriylari uchun umumiy Eloquent baza (DRY).
 * Har bir subclass faqat modelini beradi va (kerak bo'lsa) so'rovni
 * kengaytiradi (masalan eager loading, tartiblash).
 */
abstract class BaseLookupRepository implements LookupRepositoryInterface
{
    /**
     * Ushbu repozitoriy ishlaydigan model klassi.
     *
     * @return class-string<Model>
     */
    abstract protected function model(): string;

    /**
     * Ro'yxat so'rovini boyitish (eager load, tartiblash) uchun ilmoq.
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
