<?php

namespace App\Services;

use App\Data\JournalData;
use App\Models\Journal;
use App\Models\JournalType;
use App\Models\Language;
use App\Models\Publisher;
use App\Repositories\Contracts\JournalRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class JournalService
{
    public function __construct(
        private readonly JournalRepositoryInterface $journals,
    ) {}

    /**
     * Sahifalangan, filtrlangan ro'yxat.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->journals->paginate($filters, $perPage);
    }

    /**
     * Ro'yxat sahifasidagi filtr dropdown'lari uchun.
     *
     * @return array<string, mixed>
     */
    public function filterOptions(): array
    {
        return [
            'types' => JournalType::orderBy('id')->get(),
        ];
    }

    /**
     * Qo'shish/tahrirlash formasi uchun barcha variantlar.
     *
     * @return array<string, mixed>
     */
    public function formOptions(): array
    {
        return [
            'types' => JournalType::orderBy('id')->get(),
            'languages' => Language::orderBy('name')->get(),
            'publishers' => Publisher::orderBy('name')->get(),
        ];
    }

    public function create(JournalData $data): Journal
    {
        return DB::transaction(function () use ($data) {
            return $this->journals->create($data->toAttributes()); // slug — Observer
        });
    }

    public function update(Journal $journal, JournalData $data): Journal
    {
        return DB::transaction(function () use ($journal, $data) {
            return $this->journals->update($journal, $data->toAttributes());
        });
    }

    public function delete(Journal $journal): void
    {
        DB::transaction(function () use ($journal) {
            $this->journals->delete($journal);
        });
    }
}
