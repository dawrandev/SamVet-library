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
     * @return Collection<int, CatalogItem>
     */
    public function quickSearch(string $term): Collection
    {
        return $this->catalog->quickSearch($term, self::QUICK_SEARCH_LIMIT);
    }

    /**
     * @return array<string, mixed>
     */
    public function catalogData(CatalogFilters $filters): array
    {
        $items = $this->catalog->paginate($filters, self::PER_PAGE);

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
            // "Ehtimol shuni nazarda tutdingiz?" — only worth computing on the
            // rare zero-result page, not on every search.
            'didYouMean' => ($items->total() === 0 && filled($filters->search))
                ? $this->suggestions->suggest($filters->search)
                : null,
        ];
    }
}
