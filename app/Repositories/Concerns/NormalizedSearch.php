<?php

namespace App\Repositories\Concerns;

use App\Support\SearchNormalizer;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared "search box" clause for every repository whose table carries the
 * normalized `search_text` column (see SearchIndexService).
 *
 * Both sides of the comparison are normalized — the stored text once at save
 * time, the term here — so a librarian or visitor typing Cyrillic reaches a
 * Latin-script record and vice versa, with no per-script branching in any
 * caller. This is the plain-list counterpart of
 * CatalogRepository::applySmartSearch(); it deliberately has no relevance
 * ranking, because these listings are ordered by their own column (newest
 * first, inventory number, ...) rather than by match quality.
 */
trait NormalizedSearch
{
    /**
     * @param  array<int, string>  $rawColumns  columns kept OUT of search_text
     *                                          (codes such as ISBN or an
     *                                          inventory number) that should
     *                                          still be matched literally
     */
    protected function applyNormalizedSearch(Builder $query, string $term, array $rawColumns = []): void
    {
        $normalized = SearchNormalizer::normalize($term);

        if ($normalized === '' && $rawColumns === []) {
            // A term that normalizes away to nothing (punctuation only) must
            // match nothing — a bare '%%' pattern would return the whole table.
            $query->whereRaw('1 = 0');

            return;
        }

        // Wildcards are escaped rather than passed through: search_text is
        // immune (normalization drops % and _ outright), but a raw column
        // would otherwise let a stray "%" in the box match every row.
        $rawTerm = addcslashes($term, '%_\\');

        $query->where(function (Builder $q) use ($normalized, $rawTerm, $rawColumns): void {
            if ($normalized !== '') {
                $q->where('search_text', 'like', '%'.$normalized.'%');
            }

            foreach ($rawColumns as $column) {
                $q->orWhere($column, 'like', '%'.$rawTerm.'%');
            }
        });
    }
}
