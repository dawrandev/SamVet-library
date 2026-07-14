<?php

namespace App\Services;

use App\Data\ArticleData;
use App\Models\Article;
use App\Models\Journal;
use App\Models\JournalIssue;
use App\Models\Language;
use App\Models\ResourceField;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ArticleService
{
    /** Electronic (PDF) — protected disk (local, NOT public). */
    private const ELECTRONIC_DIR = 'articles/electronic';

    public function __construct(
        private readonly ArticleRepositoryInterface $articles,
    ) {}

    /**
     * Paginated, filtered list.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->articles->paginate($filters, $perPage);
    }

    /**
     * For the filter dropdowns on the list page. `kind` scopes the journal
     * dropdown to journals or newspapers only, matching the current section.
     *
     * @return array<string, mixed>
     */
    public function filterOptions(?string $kind = null): array
    {
        return [
            'journals' => Journal::query()
                ->when($kind, fn ($q) => $q->where('kind', $kind))
                ->orderBy('name')
                ->get(),
            'resourceFields' => ResourceField::orderBy('id')->get(),
        ];
    }

    /**
     * Lookups for the create/edit form selects.
     *
     * @return array<string, mixed>
     */
    public function formOptions(): array
    {
        return [
            'languages' => Language::orderBy('name')->get(),
            'resourceFields' => ResourceField::orderBy('id')->get(),
        ];
    }

    /**
     * Resolve the pre-selected journal (id + name) and issue id for the form —
     * from an explicit journal id and/or an issue id (edit mode or redisplay after
     * a validation error). Keeps the DB query out of the Blade view.
     *
     * @return array{selectedJournalId: int|null, selectedJournalName: string|null, selectedIssueId: int|null}
     */
    public function formSelection(?int $journalId, ?int $issueId): array
    {
        $journal = null;

        if ($journalId !== null) {
            $journal = Journal::find($journalId);
        } elseif ($issueId !== null) {
            $journal = JournalIssue::with('journal')->find($issueId)?->journal;
        }

        return [
            'selectedJournalId' => $journal?->id,
            'selectedJournalName' => $journal?->name,
            'selectedIssueId' => $issueId,
        ];
    }

    public function create(ArticleData $data): Article
    {
        return DB::transaction(function () use ($data) {
            $attributes = $data->toAttributes();

            if ($data->electronic_file) {
                $attributes['electronic_file'] = $this->storeProtected($data->electronic_file);
            }

            return $this->articles->create($attributes); // slug — Observer
        });
    }

    public function update(Article $article, ArticleData $data): Article
    {
        return DB::transaction(function () use ($article, $data) {
            $attributes = $data->toAttributes();

            if ($data->electronic_file) {
                $this->deleteFile($article->electronic_file);
                $attributes['electronic_file'] = $this->storeProtected($data->electronic_file);
            }

            return $this->articles->update($article, $attributes);
        });
    }

    public function delete(Article $article): void
    {
        DB::transaction(function () use ($article) {
            $this->deleteFile($article->electronic_file);

            $this->articles->delete($article);
        });
    }

    private function storeProtected(UploadedFile $file): string
    {
        return $file->store(self::ELECTRONIC_DIR, 'local');
    }

    private function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }
}
