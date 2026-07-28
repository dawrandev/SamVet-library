<?php

namespace App\Repositories\Eloquent;

use App\Data\CatalogFilters;
use App\Data\CatalogItem;
use App\Enums\BookFormat;
use App\Enums\CatalogFormat;
use App\Enums\CatalogResourceType;
use App\Enums\CatalogSearchScope;
use App\Enums\CopyStatus;
use App\Models\Audiobook;
use App\Models\Avtoreferat;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\BookType;
use App\Models\Category;
use App\Models\Dissertation;
use App\Models\Language;
use App\Models\Video;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator as PaginatorImpl;
use Illuminate\Support\Collection;

class CatalogRepository implements CatalogRepositoryInterface
{
    /**
     * Defensive cap per resource type during Phase A (see paginate()) — this
     * library's real fund is hundreds of rows per type, not thousands+, so
     * this should never actually bind. If it ever does, that's the signal
     * this needs Scout/Meilisearch (CLAUDE.md's own stated long-term plan),
     * not a bigger cap.
     */
    private const MAX_ROWS_PER_TYPE = 2000;

    /**
     * Merges Book/Audiobook/Video/Dissertation/Avtoreferat into one
     * paginated, sorted page. Two phases so eager loading only ever touches
     * the current page, never the whole eligible set:
     *
     *  A. For every resource type still eligible (per the Shakli filter and
     *     whether a book-only facet is active), fetch just enough columns to
     *     sort by (id, title/name, views_count, created_at).
     *  B. Sort the merged, lightweight rows once in PHP (ids aren't
     *     comparable across 5 tables, so this can't happen in SQL), slice
     *     the current page, then re-query only those ids per type with the
     *     eager loads their card actually needs.
     */
    public function paginate(CatalogFilters $filters, int $perPage): LengthAwarePaginator
    {
        $eligible = $this->eligibleTypes($filters);

        if ($eligible === []) {
            return $this->emptyPaginator($perPage);
        }

        $rows = collect();
        foreach ($eligible as $type) {
            $titleColumn = $type->titleColumn();

            $typeRows = $this->eligibleQuery($type, $filters)
                ->select(['id', $titleColumn, 'views_count', 'created_at'])
                ->limit(self::MAX_ROWS_PER_TYPE)
                ->get()
                ->map(fn (Model $m): array => [
                    'type' => $type->value,
                    'id' => $m->id,
                    'title' => (string) $m->{$titleColumn},
                    'views' => (int) $m->views_count,
                    'created_at' => $m->created_at,
                ]);

            $rows = $rows->concat($typeRows);
        }

        $sorted = $filters->sort->sortRows($rows);

        $page = PaginatorImpl::resolveCurrentPage();
        $total = $sorted->count();
        $slice = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

        $models = [];
        foreach ($slice->groupBy('type') as $typeValue => $pageRows) {
            $type = CatalogResourceType::from($typeValue);
            foreach ($this->hydrate($type, $pageRows->pluck('id')->all()) as $model) {
                $models[$typeValue.':'.$model->id] = $model;
            }
        }

        $items = $slice
            ->map(fn (array $r) => $models[$r['type'].':'.$r['id']] ?? null)
            // Tolerates a row deleted between phase A and phase B, though at
            // this data volume both run within the same request in practice.
            ->filter()
            ->map(fn (Model $m) => $this->toItem($m))
            ->values();

        return $this->makePaginator($items, $total, $perPage, $page);
    }

    /**
     * Which resource types the current filters leave in play. Empty Shakli
     * selection = all five; otherwise the union of what each selected option
     * maps to. Any active book-only facet (Kategoriya/Turi/Til/Yil, or the
     * ISBN search scope) narrows that down to Book alone, regardless of Shakli.
     *
     * @return array<int, CatalogResourceType>
     */
    private function eligibleTypes(CatalogFilters $filters): array
    {
        $formats = $filters->formatCases();

        $eligible = $formats === []
            ? CatalogResourceType::cases()
            : CatalogFormat::resourceTypesFor($formats);

        if ($filters->booksOnly()) {
            $eligible = array_values(array_filter(
                $eligible,
                fn (CatalogResourceType $type) => $type->supportsBookFacets()
            ));
        }

        return $eligible;
    }

    private function eligibleQuery(CatalogResourceType $type, CatalogFilters $filters): Builder
    {
        return match ($type) {
            CatalogResourceType::Book => $this->bookQuery($filters),
            CatalogResourceType::Audiobook => $this->nonBookQuery(Audiobook::query(), 'name', $filters),
            CatalogResourceType::Video => $this->nonBookQuery(Video::query(), 'name', $filters),
            CatalogResourceType::Dissertation => $this->nonBookQuery(Dissertation::query(), 'title', $filters),
            CatalogResourceType::Avtoreferat => $this->nonBookQuery(Avtoreferat::query(), 'title', $filters),
        };
    }

    private function bookQuery(CatalogFilters $filters): Builder
    {
        $bookFormatValues = CatalogFormat::bookFormatValuesFor($filters->formatCases());

        return Book::query()
            ->when($filters->search, function (Builder $query) use ($filters): void {
                $query->where(function (Builder $q) use ($filters): void {
                    match ($filters->scope) {
                        CatalogSearchScope::Title => $q->where('title', 'like', "%{$filters->search}%"),
                        CatalogSearchScope::Isbn => $q->where('isbn', 'like', "%{$filters->search}%"),
                        // "Mavzu" (topic) — the annotation is the only free-text field that
                        // actually describes subject matter, so it's what this scope searches.
                        CatalogSearchScope::Topic => $q->where('annotation', 'like', "%{$filters->search}%"),
                        default => $q->where('title', 'like', "%{$filters->search}%")
                            ->orWhere('isbn', 'like', "%{$filters->search}%")
                            ->orWhere('udc', 'like', "%{$filters->search}%")
                            ->orWhere('authors', 'like', "%{$filters->search}%"),
                    };
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
            ->when($bookFormatValues, function (Builder $query, array $formats): void {
                $query->whereHas('copies', fn (Builder $q) => $q->whereIn('format', $formats));
            })
            ->when($filters->yearFrom, fn (Builder $query, int $year) => $query->where('publication_year', '>=', $year))
            ->when($filters->yearTo, fn (Builder $query, int $year) => $query->where('publication_year', '<=', $year))
            ->when($filters->author, fn (Builder $query, string $author) => $query->where('authors', 'like', "%{$author}%"));
    }

    /**
     * Shared filter shape for Audiobook/Video/Dissertation/Avtoreferat — none
     * of them carry Kategoriya/Turi/Til/Yil, so this is just search + author.
     * The ISBN scope never reaches here — CatalogFilters::booksOnly() excludes
     * every non-Book type upstream whenever that scope is active.
     */
    private function nonBookQuery(Builder $query, string $titleColumn, CatalogFilters $filters): Builder
    {
        return $query
            ->when($filters->search, function (Builder $q) use ($filters, $titleColumn): void {
                $q->where(function (Builder $q) use ($filters, $titleColumn): void {
                    match ($filters->scope) {
                        CatalogSearchScope::Title => $q->where($titleColumn, 'like', "%{$filters->search}%"),
                        CatalogSearchScope::Topic => $q->where('annotation', 'like', "%{$filters->search}%"),
                        default => $q->where($titleColumn, 'like', "%{$filters->search}%")
                            ->orWhere('author', 'like', "%{$filters->search}%"),
                    };
                });
            })
            ->when($filters->author, fn (Builder $q, string $author) => $q->where('author', 'like', "%{$author}%"));
    }

    /**
     * Re-fetch just the current page's rows for one type, with the eager
     * loads its card needs — the only place this catalog touches those.
     *
     * @param  array<int, int>  $ids
     */
    private function hydrate(CatalogResourceType $type, array $ids): Collection
    {
        return match ($type) {
            CatalogResourceType::Book => Book::query()
                ->with(['type', 'copies:id,book_id,format'])
                ->withCount(['copies as available_copies' => fn (Builder $q) => $q->where('status', CopyStatus::Available->value)])
                ->whereIn('id', $ids)->get(),
            CatalogResourceType::Audiobook => Audiobook::query()->withCount('tracks')->whereIn('id', $ids)->get(),
            CatalogResourceType::Video => Video::query()->withCount('tracks')->whereIn('id', $ids)->get(),
            CatalogResourceType::Dissertation => Dissertation::query()->whereIn('id', $ids)->get(),
            CatalogResourceType::Avtoreferat => Avtoreferat::query()->whereIn('id', $ids)->get(),
        };
    }

    private function toItem(Model $model): CatalogItem
    {
        return match (true) {
            $model instanceof Book => CatalogItem::fromBook($model),
            $model instanceof Audiobook => CatalogItem::fromAudiobook($model),
            $model instanceof Video => CatalogItem::fromVideo($model),
            $model instanceof Dissertation => CatalogItem::fromDissertation($model),
            $model instanceof Avtoreferat => CatalogItem::fromAvtoreferat($model),
        };
    }

    /**
     * @param  Collection<int, CatalogItem>  $items
     */
    private function makePaginator(Collection $items, int $total, int $perPage, int $page): LengthAwarePaginator
    {
        // A hand-built paginator defaults its path to "/" — without this
        // explicit override every pagination/filter link on the page breaks.
        return (new PaginatorImpl($items, $total, $perPage, $page, [
            'path' => PaginatorImpl::resolveCurrentPath(),
            'pageName' => 'page',
        ]))->withQueryString();
    }

    private function emptyPaginator(int $perPage): LengthAwarePaginator
    {
        return $this->makePaginator(collect(), 0, $perPage, 1);
    }

    public function categoryFacets(): Collection
    {
        // Every category — parent and child alike — is independently
        // filterable. A parent's count still rolls up its children's books
        // too (so picking a broad parent still surfaces everything under
        // it); a child's count is just its own directly-tagged books.
        // `parentId` lets the sidebar indent children under their parent.
        $parents = Category::query()->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->select(['id', 'parent_id', 'name'])->orderBy('sort_order')->orderBy('id')])
            ->orderBy('sort_order')->orderBy('id')
            ->get();

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
        $bookCopyCount = fn (BookFormat $format): int => Book::whereHas(
            'copies', fn (Builder $q) => $q->where('format', $format->value)
        )->count();

        // Dissertations/avtoreferats have no format of their own — they're
        // PDF-only, so they fold into the Electronic count alongside books
        // that happen to have an electronic copy.
        $counts = [
            CatalogFormat::Print->value => $bookCopyCount(BookFormat::Print),
            CatalogFormat::Braille->value => $bookCopyCount(BookFormat::Braille),
            CatalogFormat::Electronic->value => $bookCopyCount(BookFormat::Electronic)
                + Dissertation::count()
                + Avtoreferat::count(),
            CatalogFormat::Audio->value => Audiobook::count(),
            CatalogFormat::Video->value => Video::count(),
        ];

        return collect(CatalogFormat::cases())->map(fn (CatalogFormat $format): array => [
            'id' => $format->value,
            'label' => $format->label(),
            'count' => $counts[$format->value],
        ])->values();
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
            ->with(['type', 'language', 'languages', 'publicationPlace', 'categories.parent', 'copies:id,book_id,format'])
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
            ->with(['type', 'copies:id,book_id,format'])
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
