<?php

namespace App\Repositories\Eloquent;

use App\Models\Journal;
use App\Repositories\Contracts\JournalRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class JournalRepository implements JournalRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Journal::query()
            ->with(['type', 'language'])
            ->withCount('issues')
            // Search (name, ISSN)
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('issn', 'like', "%{$search}%");
                });
            })
            ->when($filters['journal_type_id'] ?? null, function ($query, int $typeId) {
                $query->where('journal_type_id', $typeId);
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?Journal
    {
        return Journal::with(['type', 'language', 'publisher', 'issues'])->find($id);
    }

    public function create(array $data): Journal
    {
        return Journal::create($data);
    }

    public function update(Journal $journal, array $data): Journal
    {
        $journal->update($data);

        return $journal;
    }

    public function delete(Journal $journal): void
    {
        $journal->delete();
    }
}
