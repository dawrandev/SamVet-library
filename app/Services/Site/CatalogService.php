<?php

namespace App\Services\Site;

use App\Data\CatalogFilters;
use App\Enums\CatalogSort;
use App\Repositories\Contracts\CatalogRepositoryInterface;

/**
 * Assembles everything the public catalog page needs: the filtered book list
 * plus the sidebar facets and control options.
 */
class CatalogService
{
    private const PER_PAGE = 12;

    public function __construct(
        private readonly CatalogRepositoryInterface $catalog,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function catalogData(CatalogFilters $filters): array
    {
        $books = $this->catalog->paginate($filters, self::PER_PAGE);

        return [
            'filters' => $filters,
            'books' => $books,
            'total' => $books->total(),
            'categories' => $this->catalog->categoryFacets(),
            'types' => $this->catalog->typeFacets(),
            'languages' => $this->catalog->languageFacets(),
            'formats' => $this->catalog->formatFacets(),
            'yearBounds' => $this->catalog->yearBounds(),
            'sortOptions' => CatalogSort::cases(),
        ];
    }
}
