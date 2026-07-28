<?php

namespace App\Repositories\Contracts;

use App\Data\CatalogFilters;
use App\Data\CatalogItem;
use App\Models\Book;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Read-only data access for the public catalog. `paginate()` spans all five
 * public resource types (books, audiobooks, videos, dissertations,
 * avtoreferats) — the other methods stay book-only, matching the facets
 * (Kategoriya/Turi/Til/Yil) that only ever apply to books.
 */
interface CatalogRepositoryInterface
{
    /**
     * Filtered, ordered, paginated list of catalog items across all five
     * resource types, merged into one page.
     *
     * @return LengthAwarePaginator<int, CatalogItem>
     */
    public function paginate(CatalogFilters $filters, int $perPage): LengthAwarePaginator;

    /**
     * Sidebar facets: every category, parent and child alike. `parentId` is
     * null for a top-level row, or its parent's id for a child row (so the
     * sidebar can indent it).
     *
     * @return Collection<int, array{id: int, label: string, count: int, parentId: ?int}>
     */
    public function categoryFacets(): Collection;

    /** @return Collection<int, array{id: int, label: string, count: int}> */
    public function typeFacets(): Collection;

    /** @return Collection<int, array{id: int, label: string, count: int}> */
    public function languageFacets(): Collection;

    /**
     * Sidebar facets for the unified "Shakli" filter. `id` is the
     * CatalogFormat enum's string value (print/electronic/braille/audio/video).
     * Electronic folds in every dissertation and avtoreferat, since neither
     * has a format of its own — they're PDF-only.
     *
     * @return Collection<int, array{id: string, label: string, count: int}>
     */
    public function formatFacets(): Collection;

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
