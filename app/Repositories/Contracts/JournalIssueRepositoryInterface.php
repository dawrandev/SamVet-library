<?php

namespace App\Repositories\Contracts;

use App\Models\JournalIssue;

interface JournalIssueRepositoryInterface
{
    public function create(array $data): JournalIssue;

    public function update(JournalIssue $issue, array $data): JournalIssue;

    public function delete(JournalIssue $issue): void;
}
