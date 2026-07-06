<?php

namespace App\Services\Lookups;

use App\Data\LookupData;
use App\Repositories\Contracts\LookupRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared business logic for all lookup management services (DRY).
 *
 * For a translatable entity (spatie HasTranslations) `name` is an array
 * (['uz'=>,'ru'=>,'kk'=>]) and is stored directly —
 * the model's `$translatable` converts it to JSON. For a plain entity
 * `name` is a string.
 */
abstract class BaseLookupService
{
    public function __construct(
        protected readonly LookupRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, Model>
     */
    public function list(): Collection
    {
        return $this->repository->all();
    }

    public function create(LookupData $data): Model
    {
        return $this->repository->create($this->attributes($data));
    }

    public function update(Model $model, LookupData $data): Model
    {
        return $this->repository->update($model, $this->attributes($data));
    }

    public function delete(Model $model): void
    {
        $this->repository->delete($model);
    }

    /**
     * Attributes written to the model from the DTO.
     *
     * @return array<string, mixed>
     */
    protected function attributes(LookupData $data): array
    {
        return ['name' => $data->name];
    }
}
