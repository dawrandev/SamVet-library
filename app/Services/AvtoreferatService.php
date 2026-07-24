<?php

namespace App\Services;

use App\Data\AvtoreferatData;
use App\Models\Avtoreferat;
use App\Models\ContributorRole;
use App\Models\PublicationPlace;
use App\Models\ScienceField;
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
        private readonly ContributorService $contributors,
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
     * Lookups for the create/edit form selects.
     *
     * @return array<string, mixed>
     */
    public function formOptions(): array
    {
        return [
            'publicationPlaces' => PublicationPlace::orderBy('id')->get(),
            'contributorRoles' => ContributorRole::orderBy('name')->get(),
            'scienceFields' => ScienceField::orderBy('name')->get(),
        ];
    }

    public function create(AvtoreferatData $data): Avtoreferat
    {
        return DB::transaction(function () use ($data) {
            $attributes = $data->toAttributes();

            if ($data->electronic_file) {
                $attributes['electronic_file'] = $this->storeProtected($data->electronic_file);
            }

            $avtoreferat = $this->avtoreferats->create($attributes); // slug — Observer

            $this->contributors->sync($avtoreferat, $data->contributors);

            return $avtoreferat;
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

            $avtoreferat = $this->avtoreferats->update($avtoreferat, $attributes);

            $this->contributors->sync($avtoreferat, $data->contributors);

            return $avtoreferat;
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
