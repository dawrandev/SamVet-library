<?php

namespace App\Services;

use App\Data\BookData;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookType;
use App\Models\Category;
use App\Models\Language;
use App\Models\Publisher;
use App\Models\Work;
use App\Repositories\Contracts\BookRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BookService
{
    public function __construct(
        private readonly BookRepositoryInterface $books,
    ) {}

    /**
     * Sahifalangan, filtrlangan ro'yxat.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->books->paginate($filters, $perPage);
    }

    /**
     * Ro'yxat sahifasidagi filtr dropdown'lari uchun.
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
     * Qo'shish/tahrirlash formasi uchun barcha variantlar.
     *
     * @return array<string, mixed>
     */
    public function formOptions(): array
    {
        return [
            'types' => BookType::orderBy('name')->get(),
            'languages' => Language::orderBy('name')->get(),
            'publishers' => Publisher::orderBy('name')->get(),
            'authors' => Author::orderBy('name')->get(),
            'categories' => Category::with('parent')->orderBy('name')->get(),
        ];
    }

    public function create(BookData $data, ?int $translationOfId = null): Book
    {
        return DB::transaction(function () use ($data, $translationOfId) {
            $attributes = $data->toAttributes();

            // Fayllar (muqova — ochiq; elektron/audio — himoyalangan)
            if ($data->cover) {
                $attributes['cover_image'] = $this->storePublic($data->cover, 'covers');
            }
            if ($data->electronic_file) {
                $attributes['electronic_file'] = $this->storeProtected($data->electronic_file, 'books/electronic');
            }
            if ($data->audio_file) {
                $attributes['audio_file'] = $this->storeProtected($data->audio_file, 'books/audio');
            }

            // Tarjima nashri bo'lsa — manba bilan bir xil asar (work) guruhiga bog'la
            if ($translationOfId) {
                $attributes['work_id'] = $this->resolveWorkId($translationOfId);
            }

            $book = $this->books->create($attributes); // slug — Observer

            $book->authors()->sync($data->author_ids);
            $book->categories()->sync($data->category_ids);

            return $book;
        });
    }

    /**
     * Manba kitobning asar (work) guruhini aniqlaydi: yo'q bo'lsa yangi Work yasab manbaga tayinlaydi.
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
            if ($data->electronic_file) {
                $this->deleteFile('local', $book->electronic_file);
                $attributes['electronic_file'] = $this->storeProtected($data->electronic_file, 'books/electronic');
            }
            if ($data->audio_file) {
                $this->deleteFile('local', $book->audio_file);
                $attributes['audio_file'] = $this->storeProtected($data->audio_file, 'books/audio');
            }

            $book = $this->books->update($book, $attributes);

            $book->authors()->sync($data->author_ids);
            $book->categories()->sync($data->category_ids);

            return $book;
        });
    }

    public function delete(Book $book): void
    {
        DB::transaction(function () use ($book) {
            $this->deleteFile('public', $book->cover_image);
            $this->deleteFile('local', $book->electronic_file);
            $this->deleteFile('local', $book->audio_file);

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

    private function deleteFile(string $disk, ?string $path): void
    {
        if ($path && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }
}
