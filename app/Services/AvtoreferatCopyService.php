<?php

namespace App\Services;

use App\Data\AvtoreferatCopyData;
use App\Models\Avtoreferat;
use App\Models\AvtoreferatCopy;
use App\Repositories\Contracts\AvtoreferatCopyRepositoryInterface;
use Illuminate\Support\Facades\DB;

class AvtoreferatCopyService
{
    public function __construct(
        private readonly AvtoreferatCopyRepositoryInterface $copies,
    ) {}

    public function create(Avtoreferat $avtoreferat, AvtoreferatCopyData $data): AvtoreferatCopy
    {
        return DB::transaction(function () use ($avtoreferat, $data) {
            $attributes = $data->toAttributes();
            $attributes['avtoreferat_id'] = $avtoreferat->id;

            return $this->copies->create($attributes);
        });
    }

    public function update(AvtoreferatCopy $copy, AvtoreferatCopyData $data): AvtoreferatCopy
    {
        return DB::transaction(fn () => $this->copies->update($copy, $data->toAttributes()));
    }

    public function delete(AvtoreferatCopy $copy): void
    {
        DB::transaction(fn () => $this->copies->delete($copy));
    }
}
