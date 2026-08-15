<?php

namespace App\Services\Site;

use App\Data\CatalogFilters;
use App\Data\CatalogItem;
use App\Enums\CatalogSort;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Assembles everything the public catalog page needs: the filtered book list
 * plus the sidebar facets and control options.
 */
class CatalogService
{
    private const PER_PAGE = 12;

    /** Cap for the live typeahead dropdown — a short, scannable list. */
    private const QUICK_SEARCH_LIMIT = 8;

    public function __construct(
        private readonly CatalogRepositoryInterface $catalog,
        private readonly SearchSuggestionService $suggestions,
    ) {}

    /**
     * A misspelled term falls back to the closest real catalog word, so a
     * typo still surfaces the resource the visitor meant — not just a "did
     * you mean" link to click through.
     *
     * @return Collection<int, CatalogItem>
     */
    public function quickSearch(string $term): Collection
    {
        $results = $this->catalog->quickSearch($term, self::QUICK_SEARCH_LIMIT);

        if (blank($term)) {
            return $results;
        }

        $corrected = $this->suggestions->suggest($term);

        if ($corrected === null) {
            return $results;
        }

        $correctedResults = $this->catalog->quickSearch($corrected, self::QUICK_SEARCH_LIMIT);

        return $correctedResults->isNotEmpty() ? $correctedResults : $results;
    }

    /**
     * @return array<string, mixed>
     */
    public function catalogData(CatalogFilters $filters): array
    {
        $items = $this->catalog->paginate($filters, self::PER_PAGE);
        $correctedSearch = null;

        // A misspelled search shows the corrected term's results directly
        // (like a browser search engine), instead of making the visitor
        // click a separate "did you mean" suggestion to see anything.
        //
        // The trigger is "the query contains a word the catalog has never
        // heard of", not "the query found nothing". Gating on a zero (or low)
        // result count misses the commonest real typo — one misspelled word
        // beside a correct one. "vetrenariya asoslari" still matches every
        // book whose title ends in "asoslari", so the count never looks bad,
        // yet every one of those hits is the wrong book. suggest() returns
        // null whenever every word is already spelled the way the catalog
        // spells it, so a correct query costs nothing and is never rewritten.
        if (filled($filters->search)) {
            $suggestion = $this->suggestions->suggest($filters->search);

            if ($suggestion !== null) {
                $correctedItems = $this->catalog->paginate($filters->withSearch($suggestion), self::PER_PAGE);

                // The corrected word can still turn up nothing once combined
                // with the rest of the active filters — don't claim a
                // correction that didn't actually find anything.
                if ($correctedItems->total() > 0) {
                    $items = $correctedItems;
                    $correctedSearch = $suggestion;
                }
            }
        }

        return [
            'filters' => $filters,
            'items' => $items,
            'total' => $items->total(),
            'categories' => $this->catalog->categoryFacets(),
            'types' => $this->catalog->typeFacets(),
            'languages' => $this->catalog->languageFacets(),
            'formats' => $this->catalog->formatFacets(),
            'yearBounds' => $this->catalog->yearBounds(),
            'sortOptions' => CatalogSort::cases(),
            'correctedSearch' => $correctedSearch,
        ];
    }
}
