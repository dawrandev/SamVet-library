<?php

namespace App\Services;

use App\Data\AvtoreferatData;
use App\Enums\ChunkedUploadKind;
use App\Models\Avtoreferat;
use App\Models\Category;
use App\Models\ContributorRole;
use App\Models\Language;
use App\Models\PublicationPlace;
use App\Models\ScienceField;
use App\Repositories\Contracts\AvtoreferatRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AvtoreferatService
{
    /** Electronic (PDF) — protected disk (local, NOT public). */
    private const ELECTRONIC_DIR = 'avtoreferats/electronic';

    public function __construct(
        private readonly AvtoreferatRepositoryInterface $avtoreferats,
        private readonly ContributorService $contributors,
        private readonly ChunkedUploadService $chunkedUploads,
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
            'categories' => $this->categoryOptions(),
            'publicationPlaces' => PublicationPlace::orderBy('id')->get(),
            'contributorRoles' => ContributorRole::orderBy('name')->get(),
            'scienceFields' => ScienceField::orderBy('name')->get(),
            'languages' => Language::orderBy('name')->get(),
        ];
    }

    /** "Parent › Child" labeled options for the category select — flat list, hierarchy shown in the label. */
    private function categoryOptions(): Collection
    {
        return Category::with('parent')->orderBy('name')->get()->map(fn (Category $c) => (object) [
            'id' => $c->id,
            'name' => $c->parent ? $c->parent->name.' › '.$c->name : $c->name,
        ]);
    }

    public function create(AvtoreferatData $data): Avtoreferat
    {
        return DB::transaction(function () use ($data) {
            $attributes = $data->toAttributes();

            if ($path = $this->resolveElectronicFile($data)) {
                $attributes['electronic_file'] = $path;
            }

            $avtoreferat = $this->avtoreferats->create($attributes); // slug — Observer

            $this->contributors->sync($avtoreferat, $data->contributors);
            $avtoreferat->languages()->sync($data->language_ids);

            return $avtoreferat;
        });
    }

    public function update(Avtoreferat $avtoreferat, AvtoreferatData $data): Avtoreferat
    {
        return DB::transaction(function () use ($avtoreferat, $data) {
            $attributes = $data->toAttributes();

            if ($path = $this->resolveElectronicFile($data)) {
                $this->deleteFile($avtoreferat->electronic_file);
                $attributes['electronic_file'] = $path;
            }

            $avtoreferat = $this->avtoreferats->update($avtoreferat, $attributes);

            $this->contributors->sync($avtoreferat, $data->contributors);
            $avtoreferat->languages()->sync($data->language_ids);

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

    /**
     * Either a direct upload (electronic_file) or one assembled via chunked
     * upload (electronic_file_token) — never both, the FormRequest guards
     * that. Null when neither was given (e.g. an update leaving the file be).
     */
    private function resolveElectronicFile(AvtoreferatData $data): ?string
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
