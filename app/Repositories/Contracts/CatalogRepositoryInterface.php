<?php

namespace App\Repositories\Contracts;

use App\Data\CatalogFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Read-only data access for the public catalog (books visible to visitors).
 * Separate from the admin BookRepository: only public-safe fields/relations.
 */
interface CatalogRepositoryInterface
{
    /** Filtered, ordered, paginated list of books. */
    public function paginate(CatalogFilters $filters, int $perPage): LengthAwarePaginator;

    /**
     * Sidebar facets: each item is {id, label, count}.
     *
     * @return Collection<int, array{id: int, label: string, count: int}>
     */
    public function categoryFacets(): Collection;

    /** @return Collection<int, array{id: int, label: string, count: int}> */
    public function typeFacets(): Collection;

    /** @return Collection<int, array{id: int, label: string, count: int}> */
    public function languageFacets(): Collection;

    /**
     * Min/max publication year across the fund (for the year range inputs).
     *
     * @return array{min: ?int, max: ?int}
     */
    public function yearBounds(): array;
}
