<?php

namespace App\Repositories\Eloquent;

use App\Enums\CopyStatus;
use App\Models\Book;
use App\Models\Category;
use App\Repositories\Contracts\BookRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class BookRepository implements BookRepositoryInterface
{
    public function filtered(array $filters = []): Builder
    {
        return Book::query()
            // Search (title, ISBN, UDC)
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('isbn', 'like', "%{$search}%")
                        ->orWhere('udc', 'like', "%{$search}%");
                });
            })
            ->when($filters['category_id'] ?? null, function ($query, int $categoryId) {
                // A parent category id must also surface books tagged only with
                // one of its children — mirrors the public catalog's own
                // category expansion (CatalogRepository::bookQuery()).
                $expandedIds = Category::query()->where(
                    fn ($q) => $q->whereIn('id', [$categoryId])->orWhere('parent_id', $categoryId)
                )->pluck('id');

                $query->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $expandedIds));
            })
            ->when($filters['language_id'] ?? null, function ($query, int $languageId) {
                $query->where('language_id', $languageId);
            });
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->filtered($filters)
            ->with(['type', 'language'])
            ->withCount([
                'copies',
                'copies as available_copies_count' => fn ($q) => $q->where('status', CopyStatus::Available->value),
            ])
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?Book
    {
        return Book::with(['type', 'language', 'languages', 'publicationPlace', 'categories', 'copies.location'])
            ->find($id);
    }

    public function create(array $data): Book
    {
        return Book::create($data);
    }

    public function update(Book $book, array $data): Book
    {
        $book->update($data);

        return $book;
    }

    public function delete(Book $book): void
    {
        $book->delete();
    }
}
