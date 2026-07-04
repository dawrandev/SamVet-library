<?php

namespace App\Services;

use App\Data\JournalCopyData;
use App\Models\JournalCopy;
use App\Models\JournalIssue;
use App\Repositories\Contracts\JournalCopyRepositoryInterface;
use Illuminate\Support\Facades\DB;

class JournalCopyService
{
    public function __construct(
        private readonly JournalCopyRepositoryInterface $copies,
    ) {}

    public function create(JournalIssue $issue, JournalCopyData $data): JournalCopy
    {
        return DB::transaction(function () use ($issue, $data) {
            $attributes = $data->toAttributes();
            $attributes['journal_issue_id'] = $issue->id;

            return $this->copies->create($attributes);
        });
    }

    public function update(JournalCopy $copy, JournalCopyData $data): JournalCopy
    {
        return DB::transaction(function () use ($copy, $data) {
            return $this->copies->update($copy, $data->toAttributes());
        });
    }

    public function delete(JournalCopy $copy): void
    {
        DB::transaction(function () use ($copy) {
            $this->copies->delete($copy);
        });
    }
}
