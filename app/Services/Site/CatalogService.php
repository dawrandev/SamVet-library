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
     * A misspelled term that matches nothing directly falls back to the
     * closest real catalog word, so a typo still surfaces the resource the
     * visitor meant — not just a "did you mean" link to click through.
     *
     * @return Collection<int, CatalogItem>
     */
    public function quickSearch(string $term): Collection
    {
        $results = $this->catalog->quickSearch($term, self::QUICK_SEARCH_LIMIT);

        if ($results->isNotEmpty() || blank($term)) {
            return $results;
        }

        $corrected = $this->suggestions->suggest($term);

        return $corrected !== null
            ? $this->catalog->quickSearch($corrected, self::QUICK_SEARCH_LIMIT)
            : $results;
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
        if ($items->total() === 0 && filled($filters->search)) {
            $correctedSearch = $this->suggestions->suggest($filters->search);

            if ($correctedSearch !== null) {
                $items = $this->catalog->paginate($filters->withSearch($correctedSearch), self::PER_PAGE);

                // The corrected word can still turn up nothing once combined
                // with the rest of the active filters — don't claim a
                // correction that didn't actually find anything.
                if ($items->total() === 0) {
                    $correctedSearch = null;
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
