<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Audiobook;
use App\Models\Avtoreferat;
use App\Models\Book;
use App\Models\Dissertation;
use App\Models\Journal;
use App\Models\Reader;
use App\Models\Video;
use App\Support\SearchNormalizer;
use Illuminate\Database\Eloquent\Model;

/**
 * Keeps every searchable row's derived columns (`search_text`,
 * `title_normalized`) in step with its real fields, so search can run
 * entirely in normalized space (see SearchNormalizer) and stop caring which
 * script the visitor or the cataloguer typed in.
 *
 * Writes happen in one place only — fill(), called from each model's observer
 * on `saving`, so admin edits, imports and seeders all stay indexed without
 * any caller having to remember to. reindex() re-derives everything for the
 * `search:reindex` command (after a backfill, or after the normalizer's
 * transliteration table changes).
 */
class SearchIndexService
{
    /** Rows per chunk during a full reindex — keeps memory flat on big funds. */
    private const CHUNK = 200;

    /**
     * Every model with normalized search columns, mapped to the free-text
     * fields that feed them. The FIRST column is the title/name one: it also
     * gets its own `title_normalized`, which relevance scoring boosts and the
     * "Kitob nomi" scope filters on.
     *
     * Kept here as one registry rather than a method on each model: adding a
     * table to script-independent search should be a single-line change in a
     * single file, and this is also the list `search:reindex` walks.
     *
     * Note on books: `isbn` is deliberately absent. The public catalog's
     * relevance ranking is tuned for prose, and folding a digit string into
     * the same blob would let a year-like query ("2019") match an unrelated
     * ISBN. Admin's book search ORs `isbn` separately instead
     * (BookRepository::filtered()), and the public catalog keeps its own
     * dedicated ISBN scope chip.
     *
     * @var array<class-string<Model>, array<int, string>>
     */
    private const INDEXED_MODELS = [
        Book::class => ['title', 'authors', 'annotation', 'udc'],
        Audiobook::class => ['name', 'author', 'annotation'],
        Video::class => ['name', 'author', 'annotation'],
        Dissertation::class => ['title', 'author', 'annotation'],
        Avtoreferat::class => ['title', 'author', 'annotation'],
        Article::class => ['title', 'author', 'annotation', 'doi', 'external_journal_name'],
        Journal::class => ['name', 'issn', 'founder'],
        Reader::class => ['full_name', 'id_number', 'pinfl'],
    ];

    /**
     * The subset whose words may be offered as public "did you mean"
     * suggestions.
     *
     * Reader is excluded ON PURPOSE and must stay excluded: the suggestion
     * vocabulary is reachable from an unauthenticated endpoint, so feeding
     * reader names into it would turn spelling correction into a way to
     * enumerate borrowers' personal data one probe at a time.
     *
     * @var array<int, class-string<Model>>
     */
    private const PUBLIC_MODELS = [
        Book::class,
        Audiobook::class,
        Video::class,
        Dissertation::class,
        Avtoreferat::class,
        Article::class,
        Journal::class,
    ];

    /**
     * @return array<int, class-string<Model>>
     */
    public static function publicModels(): array
    {
        return self::PUBLIC_MODELS;
    }

    /**
     * Derives both search columns from the model's current attributes.
     * Called before the row is written, so it sees pending changes. A model
     * that isn't in the registry is a no-op rather than an error.
     */
    public function fill(Model $model): void
    {
        $columns = self::INDEXED_MODELS[$model::class] ?? null;

        if ($columns === null) {
            return;
        }

        $model->setAttribute(
            'title_normalized',
            SearchNormalizer::normalize((string) $model->getAttribute($columns[0]))
        );

        $model->setAttribute('search_text', SearchNormalizer::normalizeAll(
            array_map(
                static fn (string $column): ?string => $model->getAttribute($column),
                $columns
            )
        ));
    }

    /**
     * Re-derives the search columns for every indexed row, or just one model.
     * Uses saveQuietly() on purpose: a reindex is machine housekeeping, not a
     * librarian's edit, so it must not fire model events — which would both
     * re-enter this service and (for books and readers) write a bogus
     * "updated" row into the admin activity log for every single record.
     *
     * @param  class-string<Model>|null  $only
     * @return array<string, int>  rows touched, keyed by short model name
     */
    public function reindex(?string $only = null): array
    {
        $models = $only !== null ? [$only] : array_keys(self::INDEXED_MODELS);
        $counts = [];

        foreach ($models as $modelClass) {
            $touched = 0;

            $modelClass::query()->chunkById(self::CHUNK, function ($rows) use (&$touched): void {
                foreach ($rows as $row) {
                    $this->fill($row);
                    $row->saveQuietly();
                    $touched++;
                }
            });

            $counts[class_basename($modelClass)] = $touched;
        }

        return $counts;
    }

    /**
     * Resolves a `--model=book` style option to a registered model class.
     *
     * @return class-string<Model>|null
     */
    public static function resolveModel(string $name): ?string
    {
        foreach (array_keys(self::INDEXED_MODELS) as $modelClass) {
            if (strcasecmp(class_basename($modelClass), $name) === 0) {
                return $modelClass;
            }
        }

        return null;
    }
}
