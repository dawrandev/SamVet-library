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
use App\Models\Article;
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
use App\Support\SearchNormalizer;
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
     * Written in NORMALIZED form (see SearchNormalizer), because that's the
     * only shape these are ever compared against: the Russian entries below
     * are "и/или/с/на/для/это/то/в/по/а" after transliteration, and listing
     * them in Cyrillic would silently never match anything.
     *
     * @var array<int, string>
     */
    private const STOPWORDS = [
        'va', 'yoki', 'ham', 'bilan', 'uchun', 'bu', 'shu', 'u', 'da', 'de', 'ya',
        'i', 'ili', 's', 'na', 'dlya', 'eto', 'to', 'v', 'po', 'a',
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
            $query = match ($type) {
                CatalogResourceType::Book => Book::query(),
                CatalogResourceType::Audiobook => Audiobook::query(),
                CatalogResourceType::Video => Video::query(),
                CatalogResourceType::Dissertation => Dissertation::query(),
                CatalogResourceType::Avtoreferat => Avtoreferat::query(),
                CatalogResourceType::Article => Article::query(),
            };

            $this->applySmartSearch($query, $term);

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
            CatalogResourceType::Audiobook => $this->nonBookQuery(Audiobook::query(), $filters),
            CatalogResourceType::Video => $this->nonBookQuery(Video::query(), $filters),
            // Dissertation/Avtoreferat carry their own category_id, so they
            // take the categorized variant on top of the shared shape.
            CatalogResourceType::Dissertation => $this->categorizedQuery(Dissertation::query(), $filters),
            CatalogResourceType::Avtoreferat => $this->categorizedQuery(Avtoreferat::query(), $filters),
            CatalogResourceType::Article => $this->articleQuery($filters),
        };
    }

    /**
     * Articles differ from the other non-book types in one way that matters
     * here: a full text is optional. An article with no `electronic_file` is a
     * legitimate catalogue record and belongs in the unfiltered list, the same
     * way a book with no e-copy does — but it must not answer "Shakli:
     * Elektron", which is a promise that the text can be opened.
     */
    private function articleQuery(CatalogFilters $filters): Builder
    {
        return $this->nonBookQuery(Article::query(), $filters)
            ->when(
                in_array(CatalogFormat::Electronic, $filters->formatCases(), true),
                fn (Builder $q) => $q->whereNotNull('electronic_file')
            );
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
                    // Matched against the normalized title, so this chip is
                    // script-independent too (Cyrillic query, Latin record).
                    CatalogSearchScope::Title => $this->applyWordLike($query, ['title_normalized'], SearchNormalizer::normalize($filters->search)),
                    CatalogSearchScope::Isbn => $query->where('isbn', 'like', "%{$filters->search}%"),
                    // "Mavzu" (topic) — the annotation is the only free-text field that
                    // actually describes subject matter, so it's what this scope searches.
                    // Still raw (single-script): the annotation has no normalized column
                    // of its own, only its words folded into search_text alongside the
                    // title/author, which this scope must not match on.
                    CatalogSearchScope::Topic => $this->applyWordLike($query, ['annotation'], $filters->search),
                    // "Barchasi" (default/All) — relevance-ranked smart search over the
                    // normalized search_text blob covering every free-text field.
                    default => $this->applySmartSearch($query, $filters->search),
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
    private function nonBookQuery(Builder $query, CatalogFilters $filters): Builder
    {
        return $query
            ->when($filters->search, function (Builder $q) use ($filters): void {
                match ($filters->scope) {
                    CatalogSearchScope::Title => $this->applyWordLike($q, ['title_normalized'], SearchNormalizer::normalize($filters->search)),
                    CatalogSearchScope::Topic => $this->applyWordLike($q, ['annotation'], $filters->search),
                    // "Barchasi" (default/All) — relevance-ranked smart search over the
                    // normalized search_text blob covering every free-text field.
                    default => $this->applySmartSearch($q, $filters->search),
                };
            })
            ->when($filters->author, fn (Builder $q, string $author) => $q->where('author', 'like', "%{$author}%"));
    }

    /** nonBookQuery() plus the category_id filter — Dissertation/Avtoreferat only. */
    private function categorizedQuery(Builder $query, CatalogFilters $filters): Builder
    {
        return $this->nonBookQuery($query, $filters)
            ->when($filters->categories, function (Builder $q, array $ids): void {
                $q->whereIn('category_id', $this->expandCategoryIds($ids));
            });
    }

    /**
     * Relevance-ranked, script-independent search over a resource's derived
     * `search_text` column (see SearchIndexService / SearchNormalizer).
     *
     * Both sides of the comparison are normalized — the stored text once at
     * save time, the query here — so a Cyrillic term finds a Latin-script
     * record and vice versa without any per-script branching below this line.
     *
     * MATCH() is used in the WHERE clause only, never in the SELECT list.
     * That is not a style choice: on MySQL 8.0 a MATCH() in the select list
     * combined with an OR in the WHERE makes the optimizer abandon the
     * FULLTEXT index, evaluate MATCH() per row without an initialized
     * fulltext handler, and return a garbage rank — which then overflows the
     * moment it's added to anything ("SQLSTATE[22003] ... DOUBLE value is out
     * of range", MySQL error 1690, a hard 500 on every search that actually
     * matched something). Relevance is therefore scored entirely with
     * deterministic CASE/LIKE arithmetic, which also ranks better than
     * BOOLEAN MODE's own notoriously coarse scoring: an exact title prefix
     * beats a title substring, which beats a hit anywhere in the record, and
     * each individual query word adds its own smaller weight on top.
     *
     * BOOLEAN MODE (rather than NATURAL LANGUAGE MODE) is still what the
     * MATCH() clause uses, for the reasons that already applied: natural
     * language scoring treats a word present in >50% of rows as a de-facto
     * stopword — fatal in a *veterinary* library where "veterinariya" is in
     * most titles — and its index updates aren't reliably visible inside the
     * uncommitted transaction Pest's RefreshDatabase wraps every test in.
     *
     * Escaping note: normalization strips everything outside [a-z0-9 ], so a
     * term can no longer smuggle LIKE wildcards (`%`, `_`) into these
     * patterns — that whole class of input is gone before it reaches SQL.
     */
    private function applySmartSearch(Builder $query, string $term): void
    {
        $normalized = SearchNormalizer::normalize($term);
        $words = $this->searchWords($normalized);

        if ($words === []) {
            // A term that's entirely stopwords ("va bilan") has nothing left
            // to match on — that must mean zero results, not "no filter at
            // all" (which would silently return the whole table).
            $query->whereRaw('1 = 0');

            return;
        }

        $fulltextWords = array_values(array_filter($words, fn (string $w) => mb_strlen($w) >= self::FULLTEXT_MIN_WORD_LENGTH));
        $prefixExpression = implode(' ', array_map(fn (string $w) => $w.'*', $fulltextWords));

        $query->where(function (Builder $q) use ($prefixExpression, $words, $normalized): void {
            if ($prefixExpression !== '') {
                $q->orWhereRaw('MATCH(search_text) AGAINST(? IN BOOLEAN MODE)', [$prefixExpression]);
            }

            // Whole-phrase substring — more permissive than FULLTEXT's
            // prefix-only `word*`, so an inner fragment ("terinar") still
            // matches "veterinariya".
            //
            // Length-gated for the same reason likeMatch() anchors short
            // words: a bare '%ai%' across the whole record matches inside any
            // unrelated word that happens to contain those letters
            // ("maiores"), which would make a two-letter query return most of
            // the fund. Below the floor, the anchored per-word clause below is
            // the only thing that runs.
            if (mb_strlen($normalized) >= self::FULLTEXT_MIN_WORD_LENGTH) {
                $q->orWhere('search_text', 'like', '%'.$normalized.'%');
            }

            foreach ($words as $word) {
                $this->likeMatch($q, 'search_text', $word);
            }
        });

        $query->selectRaw(...$this->relevanceExpression($normalized, $words));
    }

    /**
     * The `relevance` computed column paginate() and quickSearch() sort by:
     * a whole-phrase ladder (title prefix > title substring > anywhere in the
     * record) plus a per-word bonus so a row matching every query word
     * outranks one matching a single word.
     *
     * @param  array<int, string>  $words
     * @return array{0: string, 1: array<int, string>} [raw SQL, bindings] for selectRaw()
     */
    private function relevanceExpression(string $normalized, array $words): array
    {
        $parts = ['(CASE WHEN title_normalized LIKE ? THEN 100 WHEN title_normalized LIKE ? THEN 50 WHEN search_text LIKE ? THEN 20 ELSE 0 END)'];
        $bindings = [$normalized.'%', '%'.$normalized.'%', '%'.$normalized.'%'];

        foreach ($words as $word) {
            $parts[] = '(CASE WHEN title_normalized LIKE ? THEN 5 ELSE 0 END)';
            $bindings[] = '%'.$word.'%';
            $parts[] = '(CASE WHEN search_text LIKE ? THEN 2 ELSE 0 END)';
            $bindings[] = '%'.$word.'%';
        }

        return ['('.implode(' + ', $parts).') as relevance', $bindings];
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
            // The issue is eager-loaded for its year: an article has no
            // publication year of its own, and an external one has none at all.
            CatalogResourceType::Article => Article::query()->with('journalIssue')->whereIn('id', $ids)->get(),
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
            $model instanceof Article => CatalogItem::fromArticle($model),
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
                + Avtoreferat::count()
                // Same condition articleQuery() applies, so the facet's number
                // and the list it opens cannot disagree.
                + Article::whereNotNull('electronic_file')->count(),
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
