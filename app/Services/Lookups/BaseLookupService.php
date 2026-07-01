<?php

namespace App\Services\Lookups;

use App\Data\LookupData;
use App\Repositories\Contracts\LookupRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Barcha lookup boshqaruv service'lari uchun umumiy biznes logika (DRY).
 *
 * Tarjimali entity'da (spatie HasTranslations) `name` massiv
 * (['uz'=>,'ru'=>,'kk'=>]) bo'lib to'g'ridan-to'g'ri saqlanadi —
 * modeldagi `$translatable` uni JSON'ga aylantiradi. Oddiy entity'da
 * `name` — string.
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
     * DTO'dan modelga yoziladigan atributlar.
     *
     * @return array<string, mixed>
     */
    protected function attributes(LookupData $data): array
    {
        return ['name' => $data->name];
    }
}
