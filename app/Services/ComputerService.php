<?php

namespace App\Services;

use App\Data\ComputerData;
use App\Enums\ComputerStatus;
use App\Enums\ComputerType;
use App\Models\Computer;
use App\Repositories\Contracts\ComputerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ComputerService
{
    public function __construct(
        private readonly ComputerRepositoryInterface $computers,
    ) {}

    /**
     * Paginated, filtered list.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->computers->paginate($filters, $perPage);
    }

    /**
     * Options for filter dropdowns and the create/edit form.
     *
     * @return array<string, mixed>
     */
    public function formOptions(): array
    {
        return [
            'types' => ComputerType::cases(),
            'statuses' => ComputerStatus::cases(),
        ];
    }

    public function create(ComputerData $data): Computer
    {
        return DB::transaction(function () use ($data) {
            return $this->computers->create($data->toAttributes());
        });
    }

    public function update(Computer $computer, ComputerData $data): Computer
    {
        return DB::transaction(function () use ($computer, $data) {
            return $this->computers->update($computer, $data->toAttributes());
        });
    }

    public function delete(Computer $computer): void
    {
        DB::transaction(function () use ($computer) {
            $this->computers->delete($computer);
        });
    }
}
