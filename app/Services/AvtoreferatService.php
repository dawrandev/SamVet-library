<?php

namespace App\Services;

use App\Data\AvtoreferatData;
use App\Models\Avtoreferat;
use App\Models\Journal;
use App\Models\JournalIssue;
use App\Models\ResourceField;
use App\Repositories\Contracts\AvtoreferatRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AvtoreferatService
{
    /** Electronic (PDF) — protected disk (local, NOT public). */
    private const ELECTRONIC_DIR = 'avtoreferats/electronic';

    public function __construct(
        private readonly AvtoreferatRepositoryInterface $avtoreferats,
    ) {}

    /**
     * Paginated, filtered list.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->avtoreferats->paginate($filters, $perPage);
    }

    /**
     * For the filter dropdowns on the list page.
     *
     * @return array<string, mixed>
     */
    public function filterOptions(): array
    {
        return [
            'journals' => Journal::orderBy('name')->get(),
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

    public function create(AvtoreferatData $data): Avtoreferat
    {
        return DB::transaction(function () use ($data) {
            $attributes = $data->toAttributes();

            if ($data->electronic_file) {
                $attributes['electronic_file'] = $this->storeProtected($data->electronic_file);
            }

            return $this->avtoreferats->create($attributes); // slug — Observer
        });
    }

    public function update(Avtoreferat $avtoreferat, AvtoreferatData $data): Avtoreferat
    {
        return DB::transaction(function () use ($avtoreferat, $data) {
            $attributes = $data->toAttributes();

            if ($data->electronic_file) {
                $this->deleteFile($avtoreferat->electronic_file);
                $attributes['electronic_file'] = $this->storeProtected($data->electronic_file);
            }

            return $this->avtoreferats->update($avtoreferat, $attributes);
        });
    }

    public function delete(Avtoreferat $avtoreferat): void
    {
        DB::transaction(function () use ($avtoreferat) {
            $this->deleteFile($avtoreferat->electronic_file);

            $this->avtoreferats->delete($avtoreferat);
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
