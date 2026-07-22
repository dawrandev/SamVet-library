<?php

namespace App\Repositories\Contracts;

use App\Models\Journal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

interface JournalRepositoryInterface
{
    /**
     * Filtered (unpaginated) query builder — shared by the paginated list and exports.
     *
     * @param  array{search?: string, journal_type_id?: int, kind?: string}  $filters
     */
    public function filtered(array $filters = []): Builder;

    /**
     * Filtered, paginated list of journals. `kind` scopes to journal-only or
     * newspaper-only (App\Enums\PublicationKind) — omitted shows both.
     *
     * @param  array{search?: string, journal_type_id?: int, kind?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?Journal;

    public function create(array $data): Journal;

    public function update(Journal $journal, array $data): Journal;

    public function delete(Journal $journal): void;
}
