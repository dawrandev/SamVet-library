<?php

namespace App\Services;

use App\Data\JournalIssueData;
use App\Models\Journal;
use App\Models\JournalIssue;
use App\Repositories\Contracts\JournalIssueRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class JournalIssueService
{
    /** Muqova — ochiq disk (public). */
    private const COVERS_DIR = 'journal-covers';

    /** Elektron (PDF) — himoyalangan disk (local, public EMAS). */
    private const ELECTRONIC_DIR = 'journals/electronic';

    public function __construct(
        private readonly JournalIssueRepositoryInterface $issues,
    ) {}

    public function create(Journal $journal, JournalIssueData $data): JournalIssue
    {
        return DB::transaction(function () use ($journal, $data) {
            $attributes = $data->toAttributes();
            $attributes['journal_id'] = $journal->id;

            if ($data->cover) {
                $attributes['cover_image'] = $this->storePublic($data->cover);
            }
            if ($data->electronic_file) {
                $attributes['electronic_file'] = $this->storeProtected($data->electronic_file);
            }

            return $this->issues->create($attributes);
        });
    }

    public function update(JournalIssue $issue, JournalIssueData $data): JournalIssue
    {
        return DB::transaction(function () use ($issue, $data) {
            $attributes = $data->toAttributes();

            if ($data->cover) {
                $this->deleteFile('public', $issue->cover_image);
                $attributes['cover_image'] = $this->storePublic($data->cover);
            }
            if ($data->electronic_file) {
                $this->deleteFile('local', $issue->electronic_file);
                $attributes['electronic_file'] = $this->storeProtected($data->electronic_file);
            }

            return $this->issues->update($issue, $attributes);
        });
    }

    public function delete(JournalIssue $issue): void
    {
        DB::transaction(function () use ($issue) {
            $this->deleteFile('public', $issue->cover_image);
            $this->deleteFile('local', $issue->electronic_file);

            $this->issues->delete($issue);
        });
    }

    private function storePublic(UploadedFile $file): string
    {
        return $file->store(self::COVERS_DIR, 'public');
    }

    private function storeProtected(UploadedFile $file): string
    {
        return $file->store(self::ELECTRONIC_DIR, 'local');
    }

    private function deleteFile(string $disk, ?string $path): void
    {
        if ($path && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }
}
