<?php

namespace App\Services\Lookups;

use App\Data\ReaderTypeData;
use App\Models\ReaderType;
use App\Repositories\Eloquent\ReaderTypeRepository;
use Illuminate\Database\Eloquent\Collection;

class ReaderTypeService
{
    public function __construct(
        private readonly ReaderTypeRepository $repository,
    ) {}

    /**
     * @return Collection<int, ReaderType>
     */
    public function list(): Collection
    {
        return $this->repository->all();
    }

    public function create(ReaderTypeData $data): ReaderType
    {
        return $this->repository->create($data->toAttributes());
    }

    public function update(ReaderType $readerType, ReaderTypeData $data): ReaderType
    {
        return $this->repository->update($readerType, $data->toAttributes());
    }

    public function delete(ReaderType $readerType): void
    {
        $this->repository->delete($readerType);
    }
}
