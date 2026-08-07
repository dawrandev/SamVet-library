<?php

namespace App\Repositories\Eloquent;

use App\Data\CatalogFilters;
use App\Data\CatalogItem;
use App\Enums\BookFormat;
use App\Enums\CatalogFormat;
use App\Enums\CatalogResourceType;
use App\Enums\CatalogSearchScope;
use App\Enums\CatalogSort;
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
     * Query words shorter than this can't reliably use MySQL FULLTEXT — the
     * server's default minimum indexed word length (`innodb_ft_min_token_size`)
     * is 3, and lowering it needs a server config change plus a full reindex
     * we can't do without root access to the production host. Shorter words
     * (common in Uzbek/Karakalpak) fall back to LIKE instead, app-side only.
     */
    private const FULLTEXT_MIN_WORD_LENGTH = 3;

    /**
     * Small, app-level stopword list (Uzbek/Russian filler words) — a stand-in
     * for MySQL's own stopword table, which (like the setting above) needs
     * server config access this deploy pipeline doesn't have.
     *
     * @var array<int, string>
     */
    private const STOPWORDS = [
        'va', 'yoki', 'ham', 'bilan', 'uchun', 'bu', 'shu', 'u', 'da', 'de', 'ya',
        'и', 'или', 'с', 'на', 'для', 'это', 'то', 'в', 'по', 'а',
    ];

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

            // addSelect (not select) — applySmartSearch() may have already
            // added a `relevance` computed column inside eligibleQuery();
            // select() would silently replace, not extend, that.
            $typeRows = $this->eligibleQuery($type, $filters)
                ->addSelect(['id', $titleColumn, 'views_count', 'created_at'])
                ->limit(self::MAX_ROWS_PER_TYPE)
                ->get()
                ->map(fn (Model $m): array => [
                    'type' => $type->value,
                    'id' => $m->id,
                    'title' => (string) $m->{$titleColumn},
                    'views' => (int) $m->views_count,
                    'created_at' => $m->created_at,
                    'relevance' => (float) ($m->relevance ?? 0),
                ]);

            $rows = $rows->concat($typeRows);
        }

        // A search with no explicit sort choice ranks by relevance first —
        // the moment the user picks a real sort (Yangi/Ko'p o'qilgan/...)
        // that's respected instead, exactly like today.
        $sorted = ($filters->search !== null && $filters->sort === CatalogSort::Newest)
            ? $rows->sortByDesc(fn (array $r) => [$r['relevance'], $r['created_at']?->getTimestamp() ?? 0, $r['type'], $r['id']])->values()
            : $filters->sort->sortRows($rows);

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
     * Top matches for the live typeahead. Same relevance-scored
     * applySmartSearch() as paginate()'s default scope, across all five
     * types, no other filters — just the current page's worth of results,
     * so this stays cheap enough to call on every keystroke (debounced).
     */
    public function quickSearch(string $term, int $limit): Collection
    {
        if (trim($term) === '') {
            return collect();
        }

        $rows = collect();

        foreach (CatalogResourceType::cases() as $type) {
            $titleColumn = $type->titleColumn();
            $columns = $type === CatalogResourceType::Book
                ? ['title', 'authors', 'annotation', 'udc']
                : [$titleColumn, 'author', 'annotation'];

            $query = match ($type) {
                CatalogResourceType::Book => Book::query(),
                CatalogResourceType::Audiobook => Audiobook::query(),
                CatalogResourceType::Video => Video::query(),
                CatalogResourceType::Dissertation => Dissertation::query(),
                CatalogResourceType::Avtoreferat => Avtoreferat::query(),
            };

            $this->applySmartSearch($query, $columns, $term);

            $typeRows = $query->addSelect(['id'])
                ->limit($limit)
                ->get()
                ->map(fn (Model $m): array => [
                    'type' => $type->value,
                    'id' => $m->id,
                    'relevance' => (float) ($m->relevance ?? 0),
                ]);

            $rows = $rows->concat($typeRows);
        }

        $top = $rows->sortByDesc('relevance')->take($limit)->values();

        $models = [];
        foreach ($top->groupBy('type') as $typeValue => $group) {
            $type = CatalogResourceType::from($typeValue);
            foreach ($this->hydrate($type, $group->pluck('id')->all()) as $model) {
                $models[$typeValue.':'.$model->id] = $model;
            }
        }

        return $top
            ->map(fn (array $r) => $models[$r['type'].':'.$r['id']] ?? null)
            ->filter()
            ->map(fn (Model $m) => $this->toItem($m))
            ->values();
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
        } elseif ($filters->categoryOnly()) {
            $eligible = array_values(array_filter(
                $eligible,
                fn (CatalogResourceType $type) => $type->supportsCategoryFacet()
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
            CatalogResourceType::Dissertation => $this->categorizedQuery(Dissertation::query(), 'title', $filters),
            CatalogResourceType::Avtoreferat => $this->categorizedQuery(Avtoreferat::query(), 'title', $filters),
        };
    }

    /**
     * Selected ids can be top-level or child categories — a parent id still
     * expands to include its children (so it keeps surfacing rows tagged
     * only with a child); a child id has no children of its own, so this
     * expansion is a no-op for it and it matches exactly.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, int>
     */
    private function expandCategoryIds(array $ids): Collection
    {
        return Category::query()->where(
            fn (Builder $q) => $q->whereIn('id', $ids)->orWhereIn('parent_id', $ids)
        )->pluck('id');
    }

    private function bookQuery(CatalogFilters $filters): Builder
    {
        $bookFormatValues = CatalogFormat::bookFormatValuesFor($filters->formatCases());

        return Book::query()
            ->when($filters->search, function (Builder $query) use ($filters): void {
                match ($filters->scope) {
                    CatalogSearchScope::Title => $this->applyWordLike($query, ['title'], $filters->search),
                    CatalogSearchScope::Isbn => $query->where('isbn', 'like', "%{$filters->search}%"),
                    // "Mavzu" (topic) — the annotation is the only free-text field that
                    // actually describes subject matter, so it's what this scope searches.
                    CatalogSearchScope::Topic => $this->applyWordLike($query, ['annotation'], $filters->search),
                    // "Barchasi" (default/All) — relevance-ranked smart search across
                    // every free-text field, backed by the FULLTEXT index on
                    // (title, authors, annotation, udc).
                    default => $this->applySmartSearch($query, ['title', 'authors', 'annotation', 'udc'], $filters->search),
                };
            })
            ->when($filters->categories, function (Builder $query, array $ids): void {
                $expandedIds = $this->expandCategoryIds($ids);

                $query->whereHas('categories', fn (Builder $q) => $q->whereIn('categories.id', $expandedIds));
            })
            ->when($filters->types, fn (Builder $query, array $ids) => $query->whereIn('book_type_id', $ids))
            ->when($filters->languages, fn (Builder $query, array $ids) => $query->whereIn('language_id', $ids))
            ->when($bookFormatValues, function (Builder $query, array $formats): void {
                // Electronic has a second signal beyond a cataloged copy row —
                // books.electronic_file (see BookFormat's own docblock) — so a
                // book that's only online-readable still matches Shakli=Elektron.
                $query->where(function (Builder $q) use ($formats): void {
                    $q->whereHas('copies', fn (Builder $q2) => $q2->whereIn('format', $formats));

                    if (in_array(BookFormat::Electronic->value, $formats, true)) {
                        $q->orWhereNotNull('electronic_file');
                    }
                });
            })
            ->when($filters->yearFrom, fn (Builder $query, int $year) => $query->where('publication_year', '>=', $year))
            ->when($filters->yearTo, fn (Builder $query, int $year) => $query->where('publication_year', '<=', $year))
            ->when($filters->author, fn (Builder $query, string $author) => $query->where('authors', 'like', "%{$author}%"));
    }

    /**
     * Shared filter shape for Audiobook/Video/Dissertation/Avtoreferat — none
     * of them carry Turi/Til/Yil, so this is just search + author (+ category
     * for Dissertation/Avtoreferat, layered on top by categorizedQuery()).
     * The ISBN scope never reaches here — CatalogFilters::booksOnly() excludes
     * every non-Book type upstream whenever that scope is active.
     */
    private function nonBookQuery(Builder $query, string $titleColumn, CatalogFilters $filters): Builder
    {
        return $query
            ->when($filters->search, function (Builder $q) use ($filters, $titleColumn): void {
                match ($filters->scope) {
                    CatalogSearchScope::Title => $this->applyWordLike($q, [$titleColumn], $filters->search),
                    CatalogSearchScope::Topic => $this->applyWordLike($q, ['annotation'], $filters->search),
                    // "Barchasi" (default/All) — relevance-ranked smart search, backed by
                    // the FULLTEXT index on (title|name, author, annotation).
                    default => $this->applySmartSearch($q, [$titleColumn, 'author', 'annotation'], $filters->search),
                };
            })
            ->when($filters->author, fn (Builder $q, string $author) => $q->where('author', 'like', "%{$author}%"));
    }

    /** nonBookQuery() plus the category_id filter — Dissertation/Avtoreferat only. */
    private function categorizedQuery(Builder $query, string $titleColumn, CatalogFilters $filters): Builder
    {
        return $this->nonBookQuery($query, $titleColumn, $filters)
            ->when($filters->categories, function (Builder $q, array $ids): void {
                $q->whereIn('category_id', $this->expandCategoryIds($ids));
            });
    }

    /**
     * Relevance-ranked, typo-tolerant search across the given columns.
     * Deliberately uses BOOLEAN MODE only, not NATURAL LANGUAGE MODE:
     *  - BOOLEAN MODE with a trailing wildcard per word (`word*`) tolerates
     *    prefixes/typos (needed for the live typeahead as much as for actual
     *    misspellings).
     *  - NATURAL LANGUAGE MODE would give TF-IDF-style relevance in theory,
     *    but was dropped for two independent reasons: (1) InnoDB's
     *    natural-language scoring treats a word present in >50% of rows as a
     *    de-facto stopword — in a small, domain-heavy corpus (a *veterinary*
     *    library, where "veterinariya" appears in most titles) that's most
     *    search terms; (2) InnoDB's FULLTEXT index update isn't reliably
     *    visible to NATURAL LANGUAGE MODE within the same uncommitted
     *    transaction that inserted the row — harmless in production (real
     *    traffic reads committed data) but makes it flaky under Pest's
     *    RefreshDatabase, which wraps every test in a transaction it never
     *    commits. BOOLEAN MODE doesn't have either problem here.
     * On top of that: a title/name match is boosted directly (`+10`), not
     * left to FULLTEXT's own scoring — a title hit should outrank an
     * incidental annotation/author hit far more reliably than BOOLEAN MODE's
     * own (fairly crude) scoring guarantees on its own. Words too short for
     * FULLTEXT (below the DB's own token-length floor, which needs server
     * config we don't have to lower) fall back to word-boundary LIKE via
     * likeMatch(). Adds a `relevance` computed column that paginate() and
     * quickSearch() select and sort by.
     *
     * @param  array<int, string>  $fulltextColumns  must exactly match an existing FULLTEXT index's columns; the FIRST entry is treated as the title/name column and gets the exact-match boost
     */
    private function applySmartSearch(Builder $query, array $fulltextColumns, string $term): void
    {
        $words = $this->searchWords($term);

        if ($words === []) {
            // A term that's entirely stopwords ("va bilan") has nothing left
            // to match on — that must mean zero results, not "no filter at
            // all" (which would silently return the whole table).
            $query->whereRaw('1 = 0');

            return;
        }

        $titleColumn = $fulltextColumns[0];
        $matchColumns = implode(', ', $fulltextColumns);
        $fulltextWords = array_values(array_filter($words, fn (string $w) => mb_strlen($w) >= self::FULLTEXT_MIN_WORD_LENGTH));
        $likeWords = array_values(array_filter($words, fn (string $w) => mb_strlen($w) < self::FULLTEXT_MIN_WORD_LENGTH));
        $prefixExpression = implode(' ', array_map(fn (string $w) => $w.'*', $fulltextWords));

        $query->where(function (Builder $q) use ($fulltextColumns, $titleColumn, $prefixExpression, $likeWords, $matchColumns, $term): void {
            if ($prefixExpression !== '') {
                $q->orWhereRaw("MATCH({$matchColumns}) AGAINST(? IN BOOLEAN MODE)", [$prefixExpression]);
            }
            $q->orWhere($titleColumn, 'like', '%'.$term.'%');
            foreach ($likeWords as $word) {
                foreach ($fulltextColumns as $column) {
                    $this->likeMatch($q, $column, $word);
                }
            }
        });

        $titleBoost = "(CASE WHEN {$titleColumn} LIKE ? THEN 10 ELSE 0 END)";
        $titleBoostBinding = '%'.$term.'%';

        if ($prefixExpression !== '') {
            $query->selectRaw(
                "MATCH({$matchColumns}) AGAINST(? IN BOOLEAN MODE) + {$titleBoost} as relevance",
                [$prefixExpression, $titleBoostBinding]
            );
        } else {
            $query->selectRaw("{$titleBoost} as relevance", [$titleBoostBinding]);
        }
    }

    /**
     * A single-column, word-split, typo-tolerant LIKE search — used for the
     * "Kitob nomi"/"Mavzu" scope chips, which target one specific field
     * (not the full FULLTEXT index, so MATCH() can't be used there). No
     * relevance score; paginate() defaults it to 0 for these rows.
     *
     * @param  array<int, string>  $columns  a single column, wrapped in an array for a uniform loop with applySmartSearch()
     */
    private function applyWordLike(Builder $query, array $columns, string $term): void
    {
        $words = $this->searchWords($term);

        if ($words === []) {
            // Same reasoning as applySmartSearch()'s empty-words guard — an
            // all-stopwords term must match nothing, not everything.
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $q) use ($columns, $words): void {
            foreach ($words as $word) {
                foreach ($columns as $column) {
                    $this->likeMatch($q, $column, $word);
                }
            }
        });
    }

    /**
     * Matches one word against one column. Short words (below the FULLTEXT
     * floor) use a word-boundary-approximated LIKE — a bare `%xx%` on a
     * 2-3 letter word matches almost anything (it's a substring of dozens of
     * unrelated words), so those are anchored to look like a real standalone
     * word (start of field, end of field, or surrounded by spaces) instead.
     * Longer words keep plain substring LIKE — permissive on purpose, since
     * that's what gives prefix/typo tolerance for genuine partial words.
     */
    private function likeMatch(Builder $query, string $column, string $word): void
    {
        if (mb_strlen($word) >= self::FULLTEXT_MIN_WORD_LENGTH) {
            $query->orWhere($column, 'like', "%{$word}%");

            return;
        }

        $query->orWhere($column, 'like', "{$word} %")
            ->orWhere($column, 'like', "% {$word} %")
            ->orWhere($column, 'like', "% {$word}")
            ->orWhere($column, '=', $word);
    }

    /**
     * Lowercases, splits on whitespace, and drops stopwords.
     *
     * @return array<int, string>
     */
    private function searchWords(string $term): array
    {
        $words = preg_split('/\s+/u', mb_strtolower(trim($term)), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_diff($words, self::STOPWORDS));
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
            $parentCount = $this->categoryResourceCount($childIds->push($parent->id));
            $facets->push($this->facet($parent, $parentCount) + ['parentId' => null]);

            foreach ($parent->children as $child) {
                $childCount = $this->categoryResourceCount(collect([$child->id]));
                $facets->push($this->facet($child, $childCount) + ['parentId' => $parent->id]);
            }
        }

        return $facets;
    }

    /**
     * Books (via the book_category pivot) plus dissertations/avtoreferats
     * (via their own category_id) tagged with any of the given category ids.
     *
     * @param  Collection<int, int>  $categoryIds
     */
    private function categoryResourceCount(Collection $categoryIds): int
    {
        $bookCount = Book::whereHas('categories', fn (Builder $q) => $q->whereIn('categories.id', $categoryIds))->count();
        $dissertationCount = Dissertation::whereIn('category_id', $categoryIds)->count();
        $avtoreferatCount = Avtoreferat::whereIn('category_id', $categoryIds)->count();

        return $bookCount + $dissertationCount + $avtoreferatCount;
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

        // A book counts as Electronic via either signal: a cataloged
        // electronic BookCopy row, or a real online-readable PDF in
        // electronic_file with no copy record at all (see BookFormat's own
        // docblock). Dissertations/avtoreferats have no format of their
        // own — they're PDF-only, so they fold into Electronic too.
        $electronicBookCount = Book::where(function (Builder $q): void {
            $q->whereHas('copies', fn (Builder $q2) => $q2->where('format', BookFormat::Electronic->value))
                ->orWhereNotNull('electronic_file');
        })->count();

        $counts = [
            CatalogFormat::Print->value => $bookCopyCount(BookFormat::Print),
            CatalogFormat::Braille->value => $bookCopyCount(BookFormat::Braille),
            CatalogFormat::Electronic->value => $electronicBookCount
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
