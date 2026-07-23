<?php

namespace App\Services;

use App\Data\DissertationData;
use App\Enums\CopyCondition;
use App\Enums\DissertationType;
use App\Models\ContributorRole;
use App\Models\Dissertation;
use App\Models\DoctoralSpecialty;
use App\Models\Language;
use App\Models\MasterSpecialty;
use App\Models\PublicationPlace;
use App\Models\ResourceField;
use App\Models\ScienceField;
use App\Repositories\Contracts\DissertationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DissertationService
{
    /** Electronic (PDF) — protected disk (local, NOT public). */
    private const ELECTRONIC_DIR = 'dissertations/electronic';

    public function __construct(
        private readonly DissertationRepositoryInterface $dissertations,
        private readonly ContributorService $contributors,
    ) {}

    /**
     * Paginated, filtered list.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->dissertations->paginate($filters, $perPage);
    }

    /**
     * For the filter dropdowns on the list page.
     *
     * @return array<string, mixed>
     */
    public function filterOptions(): array
    {
        return [
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
            'contributorRoles' => ContributorRole::orderBy('name')->get(),
            'scienceFields' => ScienceField::orderBy('name')->get(),
            'doctoralSpecialties' => DoctoralSpecialty::orderBy('name')->get(),
            'masterSpecialties' => MasterSpecialty::orderBy('name')->get(),
            'languages' => Language::orderBy('id')->get(),
            'publicationPlaces' => PublicationPlace::orderBy('id')->get(),
            'degreeTypes' => DissertationType::cases(),
            'conditionOptions' => CopyCondition::cases(),
        ];
    }

    public function create(DissertationData $data): Dissertation
    {
        return DB::transaction(function () use ($data) {
            $attributes = $data->toAttributes();

            if ($data->electronic_file) {
                $attributes['electronic_file'] = $this->storeProtected($data->electronic_file);
            }

            $dissertation = $this->dissertations->create($attributes); // slug — Observer

            $this->contributors->sync($dissertation, $data->contributors);

            return $dissertation;
        });
    }

    public function update(Dissertation $dissertation, DissertationData $data): Dissertation
    {
        return DB::transaction(function () use ($dissertation, $data) {
            $attributes = $data->toAttributes();

            if ($data->electronic_file) {
                $this->deleteFile($dissertation->electronic_file);
                $attributes['electronic_file'] = $this->storeProtected($data->electronic_file);
            }

            $dissertation = $this->dissertations->update($dissertation, $attributes);

            $this->contributors->sync($dissertation, $data->contributors);

            return $dissertation;
        });
    }

    public function delete(Dissertation $dissertation): void
    {
        DB::transaction(function () use ($dissertation) {
            $this->deleteFile($dissertation->electronic_file);

            $this->dissertations->delete($dissertation);
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
