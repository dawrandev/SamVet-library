<?php

namespace App\Repositories\Contracts;

use App\Data\CatalogFilters;
use App\Models\Book;
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

    /** A single public book (by slug) with all display relations eager-loaded. */
    public function findPublicBySlug(string $slug): ?Book;

    /**
     * Books sharing a category with the given book (for the "similar" row).
     *
     * @return Collection<int, Book>
     */
    public function similar(Book $book, int $limit): Collection;

    /**
     * Distinct copy formats held for this book (drives the format tabs).
     *
     * @return Collection<int, \App\Enums\BookFormat>
     */
    public function formats(Book $book): Collection;

    /** Register one page view. */
    public function incrementViews(Book $book): void;
}
