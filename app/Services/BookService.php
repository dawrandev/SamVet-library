<?php

namespace App\Services;

use App\Data\BookData;
use App\Enums\ChunkedUploadKind;
use App\Models\Book;
use App\Models\BookType;
use App\Models\Category;
use App\Models\ContributorRole;
use App\Models\Language;
use App\Models\PublicationPlace;
use App\Models\Work;
use App\Repositories\Contracts\BookRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BookService
{
    private const ELECTRONIC_DIR = 'books/electronic';

    public function __construct(
        private readonly BookRepositoryInterface $books,
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
        return $this->books->paginate($filters, $perPage);
    }

    /**
     * For the filter dropdowns on the list page.
     *
     * @return array<string, mixed>
     */
    public function filterOptions(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(),
            'languages' => Language::orderBy('name')->get(),
        ];
    }

    /**
     * All options for the create/edit form.
     *
     * @return array<string, mixed>
     */
    public function formOptions(): array
    {
        return [
            'types' => BookType::orderBy('name')->get(),
            'languages' => Language::orderBy('name')->get(),
            'publicationPlaces' => PublicationPlace::orderBy('name')->get(),
            'categories' => Category::with('parent')->orderBy('name')->get(),
            'contributorRoles' => ContributorRole::orderBy('name')->get(),
        ];
    }

    public function create(BookData $data, ?int $translationOfId = null): Book
    {
        return DB::transaction(function () use ($data, $translationOfId) {
            $attributes = $data->toAttributes();

            // Files (cover — public; electronic — protected)
            if ($data->cover) {
                $attributes['cover_image'] = $this->storePublic($data->cover, 'covers');
            }
            if ($path = $this->resolveElectronicFile($data)) {
                $attributes['electronic_file'] = $path;
            }

            // If it is a translation edition — link it to the same work group as the source
            if ($translationOfId) {
                $attributes['work_id'] = $this->resolveWorkId($translationOfId);
            }

            $book = $this->books->create($attributes); // slug — Observer

            $book->categories()->sync($data->category_ids);
            $book->languages()->sync($data->language_ids);
            $this->contributors->sync($book, $data->contributors);

            return $book;
        });
    }

    /**
     * Resolves the source book's work group: if none exists, creates a new Work and assigns it to the source.
     */
    private function resolveWorkId(int $sourceBookId): int
    {
        $source = Book::findOrFail($sourceBookId);

        if ($source->work_id === null) {
            $work = Work::create();
            $source->work_id = $work->id;
            $source->save();
        }

        return $source->work_id;
    }

    public function update(Book $book, BookData $data): Book
    {
        return DB::transaction(function () use ($book, $data) {
            $attributes = $data->toAttributes();

            if ($data->cover) {
                $this->deleteFile('public', $book->cover_image);
                $attributes['cover_image'] = $this->storePublic($data->cover, 'covers');
            }
            if ($path = $this->resolveElectronicFile($data)) {
                $this->deleteFile('local', $book->electronic_file);
                $attributes['electronic_file'] = $path;
            }

            $book = $this->books->update($book, $attributes);

            $book->categories()->sync($data->category_ids);
            $book->languages()->sync($data->language_ids);
            $this->contributors->sync($book, $data->contributors);

            return $book;
        });
    }

    public function delete(Book $book): void
    {
        DB::transaction(function () use ($book) {
            $this->deleteFile('public', $book->cover_image);
            $this->deleteFile('local', $book->electronic_file);

            $this->books->delete($book);
        });
    }

    private function storePublic(UploadedFile $file, string $dir): string
    {
        return $file->store($dir, 'public');
    }

    private function storeProtected(UploadedFile $file, string $dir): string
    {
        return $file->store($dir, 'local');
    }

    /**
     * Either a direct upload (electronic_file) or one assembled via chunked
     * upload (electronic_file_token) — never both, FormRequest guards that.
     * Returns null when neither was given (e.g. an update that didn't touch
     * the file).
     */
    private function resolveElectronicFile(BookData $data): ?string
    {
        if ($data->electronic_file_token) {
            return $this->chunkedUploads->claimAndMove($data->electronic_file_token, ChunkedUploadKind::Pdf, Auth::guard('web')->user(), self::ELECTRONIC_DIR);
        }

        return $data->electronic_file ? $this->storeProtected($data->electronic_file, self::ELECTRONIC_DIR) : null;
    }

    private function deleteFile(string $disk, ?string $path): void
    {
        if ($path && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }
}
