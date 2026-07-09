<?php

namespace App\Repositories\Eloquent;

use App\Data\CatalogFilters;
use App\Enums\CopyStatus;
use App\Models\Book;
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
            ->with(['type', 'authors'])
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
            ->when($filters->categories, fn (Builder $query, array $ids) => $query->whereHas(
                'categories', fn (Builder $q) => $q->whereIn('categories.id', $ids)
            ))
            ->when($filters->types, fn (Builder $query, array $ids) => $query->whereIn('book_type_id', $ids))
            ->when($filters->languages, fn (Builder $query, array $ids) => $query->whereIn('language_id', $ids))
            ->when($filters->yearFrom, fn (Builder $query, int $year) => $query->where('publication_year', '>=', $year))
            ->when($filters->yearTo, fn (Builder $query, int $year) => $query->where('publication_year', '<=', $year))
            ->when($filters->author, fn (Builder $query, string $author) => $query->whereHas(
                'authors', fn (Builder $q) => $q->where('name', 'like', "%{$author}%")
            ));

        $filters->sort->apply($query);

        return $query->paginate($perPage)->withQueryString();
    }

    public function categoryFacets(): Collection
    {
        return Category::query()
            ->withCount('books')
            ->orderBy('id')
            ->get()
            ->map(fn (Category $category): array => $this->facet($category, $category->books_count));
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
