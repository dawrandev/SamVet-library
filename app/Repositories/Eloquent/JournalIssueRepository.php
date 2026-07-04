<?php

namespace App\Repositories\Eloquent;

use App\Models\JournalIssue;
use App\Repositories\Contracts\JournalIssueRepositoryInterface;

class JournalIssueRepository implements JournalIssueRepositoryInterface
{
    public function create(array $data): JournalIssue
    {
        return JournalIssue::create($data);
    }

    public function update(JournalIssue $issue, array $data): JournalIssue
    {
        $issue->update($data);

        return $issue;
    }

    public function delete(JournalIssue $issue): void
    {
        $issue->delete();
    }
}
