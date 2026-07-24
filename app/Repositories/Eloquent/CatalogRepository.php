<?php

namespace App\Repositories\Eloquent;

use App\Data\CatalogFilters;
use App\Enums\BookFormat;
use App\Enums\CopyStatus;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\BookType;
use App\Models\Category;
use App\Models\Language;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CatalogRepository implements CatalogRepositoryInterface
{
    public function paginate(CatalogFilters $filters, int $perPage): LengthAwarePaginator
    {
        $query = Book::query()
            ->with(['type'])
            ->withCount([
                'copies as available_copies' => fn (Builder $q) => $q->where('status', CopyStatus::Available->value),
            ])
            ->when($filters->search, function (Builder $query, string $search): void {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('isbn', 'like', "%{$search}%")
                        ->orWhere('udc', 'like', "%{$search}%");
                });
            })
            ->when($filters->categories, function (Builder $query, array $ids): void {
                // Selected ids can be top-level or child categories now. A parent id
                // still expands to include its children (so it keeps surfacing books
                // tagged only with a child); a child id has no children of its own,
                // so this expansion is a no-op for it and it matches exactly.
                $expandedIds = Category::query()->where(
                    fn (Builder $q) => $q->whereIn('id', $ids)->orWhereIn('parent_id', $ids)
                )->pluck('id');

                $query->whereHas('categories', fn (Builder $q) => $q->whereIn('categories.id', $expandedIds));
            })
            ->when($filters->types, fn (Builder $query, array $ids) => $query->whereIn('book_type_id', $ids))
            ->when($filters->languages, fn (Builder $query, array $ids) => $query->whereIn('language_id', $ids))
            ->when($filters->formats, function (Builder $query, array $formats): void {
                $query->whereHas('copies', fn (Builder $q) => $q->whereIn('format', $formats));
            })
            ->when($filters->yearFrom, fn (Builder $query, int $year) => $query->where('publication_year', '>=', $year))
            ->when($filters->yearTo, fn (Builder $query, int $year) => $query->where('publication_year', '<=', $year))
            ->when($filters->author, fn (Builder $query, string $author) => $query->where('authors', 'like', "%{$author}%"));

        $filters->sort->apply($query);

        return $query->paginate($perPage)->withQueryString();
    }

    public function categoryFacets(): Collection
    {
        // Every category — parent and child alike — is independently
        // filterable. A parent's count still rolls up its children's books
        // too (so picking a broad parent still surfaces everything under
        // it); a child's count is just its own directly-tagged books.
        // `parentId` lets the sidebar indent children under their parent.
        $parents = Category::query()->whereNull('parent_id')->with('children:id,parent_id,name')->orderBy('id')->get();

        $facets = collect();

        foreach ($parents as $parent) {
            $childIds = $parent->children->pluck('id');
            $parentCount = Book::whereHas('categories', fn (Builder $q) => $q->whereIn('categories.id', $childIds->push($parent->id)))->count();
            $facets->push($this->facet($parent, $parentCount) + ['parentId' => null]);

            foreach ($parent->children as $child) {
                $childCount = Book::whereHas('categories', fn (Builder $q) => $q->where('categories.id', $child->id))->count();
                $facets->push($this->facet($child, $childCount) + ['parentId' => $parent->id]);
            }
        }

        return $facets;
    }

    public function typeFacets(): Collection
    {
        return BookType::query()
            ->withCount('books')
            ->orderBy('id')
            ->get()
            ->map(fn (BookType $type): array => $this->facet($type, $type->books_count));
    }

    public function languageFacets(): Collection
    {
        return Language::query()
            ->withCount('books')
            ->orderBy('id')
            ->get()
            ->map(fn (Language $language): array => $this->facet($language, $language->books_count));
    }

    public function formatFacets(): Collection
    {
        return collect(BookFormat::cases())->map(function (BookFormat $format): array {
            $count = Book::whereHas('copies', fn (Builder $q) => $q->where('format', $format->value))->count();

            return ['id' => $format->value, 'label' => $format->label(), 'count' => $count];
        })->values();
    }

    public function yearBounds(): array
    {
        $bounds = Book::query()
            ->selectRaw('MIN(publication_year) as min_year, MAX(publication_year) as max_year')
            ->first();

        return [
            'min' => $bounds?->min_year !== null ? (int) $bounds->min_year : null,
            'max' => $bounds?->max_year !== null ? (int) $bounds->max_year : null,
        ];
    }

    public function findPublicBySlug(string $slug): ?Book
    {
        return Book::query()
            ->with(['type', 'language', 'languages', 'publicationPlace', 'categories.parent'])
            ->withCount([
                'copies as available_copies' => fn (Builder $q) => $q->where('status', CopyStatus::Available->value),
            ])
            ->where('slug', $slug)
            ->first();
    }

    public function similar(Book $book, int $limit): Collection
    {
        $categoryIds = $book->categories->pluck('id');

        if ($categoryIds->isEmpty()) {
            return collect();
        }

        return Book::query()
            ->with(['type'])
            ->withCount([
                'copies as available_copies' => fn (Builder $q) => $q->where('status', CopyStatus::Available->value),
            ])
            ->whereKeyNot($book->id)
            ->whereHas('categories', fn (Builder $q) => $q->whereIn('categories.id', $categoryIds))
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function formats(Book $book): Collection
    {
        return BookCopy::query()
            ->where('book_id', $book->id)
            ->select('format')
            ->distinct()
            ->get()
            ->pluck('format');
    }

    public function incrementViews(Book $book): void
    {
        $book->increment('views_count');
    }

    /**
     * Shape a translatable lookup model into a {id, label, count} facet row.
     *
     * @return array{id: int, label: string, count: int}
     */
    private function facet(Model $model, int $count): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $model->id,
            'label' => $model->getTranslation('name', $locale, false)
                ?: $model->getTranslation('name', 'uz', false),
            'count' => $count,
        ];
    }
}
