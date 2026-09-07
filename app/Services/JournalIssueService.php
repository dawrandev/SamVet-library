<?php

namespace App\Services;

use App\Data\JournalIssueData;
use App\Enums\ChunkedUploadKind;
use App\Models\Journal;
use App\Models\JournalIssue;
use App\Repositories\Contracts\JournalIssueRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class JournalIssueService
{
    /** Cover — public disk. */
    private const COVERS_DIR = 'journal-covers';

    /** Electronic (PDF) — protected disk (local, NOT public). */
    private const ELECTRONIC_DIR = 'journals/electronic';

    public function __construct(
        private readonly JournalIssueRepositoryInterface $issues,
        private readonly ChunkedUploadService $chunkedUploads,
    ) {}

    /**
     * Either a direct upload (electronic_file) or one assembled via chunked
     * upload (electronic_file_token) — never both, the FormRequest guards
     * that. Null when neither was given (e.g. an update leaving the file be).
     */
    private function resolveElectronicFile(JournalIssueData $data): ?string
    {
        if ($data->electronic_file_token) {
            return $this->chunkedUploads->claimAndMove(
                $data->electronic_file_token,
                ChunkedUploadKind::Pdf,
                Auth::guard('web')->user(),
                self::ELECTRONIC_DIR
            );
        }

        return $data->electronic_file ? $this->storeProtected($data->electronic_file) : null;
    }

    public function create(Journal $journal, JournalIssueData $data): JournalIssue
    {
        return DB::transaction(function () use ($journal, $data) {
            $attributes = $data->toAttributes();
            $attributes['journal_id'] = $journal->id;

            if ($data->cover) {
                $attributes['cover_image'] = $this->storePublic($data->cover);
            }
            if ($path = $this->resolveElectronicFile($data)) {
                $attributes['electronic_file'] = $path;
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
            if ($path = $this->resolveElectronicFile($data)) {
                $this->deleteFile('local', $issue->electronic_file);
                $attributes['electronic_file'] = $path;
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
