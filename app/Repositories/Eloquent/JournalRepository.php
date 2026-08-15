<?php

namespace App\Repositories\Eloquent;

use App\Models\Journal;
use App\Repositories\Concerns\NormalizedSearch;
use App\Repositories\Contracts\JournalRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class JournalRepository implements JournalRepositoryInterface
{
    use NormalizedSearch;

    public function filtered(array $filters = []): Builder
    {
        return Journal::query()
            // Search — script-independent; search_text covers name, ISSN and
            // founder (see SearchIndexService).
            ->when(
                $filters['search'] ?? null,
                fn (Builder $query, string $search) => $this->applyNormalizedSearch($query, $search)
            )
            ->when($filters['journal_type_id'] ?? null, function ($query, int $typeId) {
                $query->where('journal_type_id', $typeId);
            })
            ->when($filters['kind'] ?? null, function ($query, string $kind) {
                $query->where('kind', $kind);
            });
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->filtered($filters)
            ->with(['type', 'language'])
            ->withCount('issues')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?Journal
    {
        return Journal::with(['type', 'language', 'publicationPlace', 'issues'])->find($id);
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
