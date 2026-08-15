<?php

namespace App\Repositories\Eloquent;

use App\Enums\ReaderStatus;
use App\Models\Reader;
use App\Repositories\Concerns\NormalizedSearch;
use App\Repositories\Contracts\ReaderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ReaderRepository implements ReaderRepositoryInterface
{
    use NormalizedSearch;

    public function filtered(array $filters = []): Builder
    {
        return Reader::query()
            // Search (full name, ID number, PINFL) — script-independent, so a
            // librarian finds "Oʻlmasov" by typing "Ўлмасов" and vice versa.
            ->when(
                $filters['search'] ?? null,
                fn (Builder $query, string $search) => $this->applyNormalizedSearch($query, $search)
            )
            ->when($filters['type'] ?? null, function ($query, string $type) {
                $query->where('reader_type_id', $type);
            })
            ->when($filters['status'] ?? null, function ($query, string $status) {
                $query->where('status', $status);
            })
            // When no status filter is given, "Left" members are hidden from the main list.
            // (The record is kept; to view them, select the status=left filter.)
            ->when(empty($filters['status']), function ($query) {
                $query->where('status', '!=', ReaderStatus::Left->value);
            });
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->filtered($filters)
            ->with(['affiliationGroup', 'type'])
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?Reader
    {
        return Reader::find($id);
    }

    public function findByIdNumber(string $idNumber): ?Reader
    {
        return Reader::where('id_number', $idNumber)->first();
    }

    public function create(array $data): Reader
    {
        return Reader::create($data);
    }

    public function update(Reader $reader, array $data): Reader
    {
        $reader->update($data);

        return $reader;
    }

    public function delete(Reader $reader): void
    {
        $reader->delete();
    }
}
