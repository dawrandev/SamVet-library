<?php

namespace App\Data;

use App\Enums\CatalogFormat;
use App\Enums\CatalogSearchScope;
use App\Enums\CatalogSort;

/**
 * Immutable set of catalog filter criteria passed from the controller to the
 * service/repository layer (instead of a loose $data array).
 */
final class CatalogFilters
{
    /**
     * @param  array<int, int>  $categories  selected category ids (parent or child)
     * @param  array<int, int>  $types  selected book type ids
     * @param  array<int, int>  $languages  selected language ids
     * @param  array<int, string>  $formats  selected BookFormat enum values
     */
    public function __construct(
        public readonly ?string $search = null,
        public readonly array $categories = [],
        public readonly array $types = [],
        public readonly array $languages = [],
        public readonly array $formats = [],
        public readonly ?int $yearFrom = null,
        public readonly ?int $yearTo = null,
        public readonly ?string $author = null,
        public readonly CatalogSort $sort = CatalogSort::Newest,
        public readonly CatalogSearchScope $scope = CatalogSearchScope::All,
    ) {}

    /** Same filters with only the search term swapped — used for the typo-correction retry. */
    public function withSearch(?string $search): self
    {
        return new self(
            search: $search,
            categories: $this->categories,
            types: $this->types,
            languages: $this->languages,
            formats: $this->formats,
            yearFrom: $this->yearFrom,
            yearTo: $this->yearTo,
            author: $this->author,
            sort: $this->sort,
            scope: $this->scope,
        );
    }

    /** True when at least one narrowing filter is applied (sort excluded). */
    public function isActive(): bool
    {
        return $this->search !== null
            || $this->categories !== []
            || $this->types !== []
            || $this->languages !== []
            || $this->formats !== []
            || $this->yearFrom !== null
            || $this->yearTo !== null
            || $this->author !== null;
    }

    /**
     * Selected "Shakli" options as enum cases (invalid/unknown values dropped).
     *
     * @return array<int, CatalogFormat>
     */
    public function formatCases(): array
    {
        return array_values(array_filter(array_map(
            fn (string $value) => CatalogFormat::tryFrom($value),
            $this->formats,
        )));
    }

    /**
     * True when a book-only facet is in play. Turi/Til/Yil and the ISBN
     * search scope have no counterpart on audiobooks, videos, dissertations
     * or avtoreferats — using one narrows the whole catalog down to books.
     * Intentional, not a gap. Kategoriya is NOT included here — Book,
     * Dissertation and Avtoreferat all carry a category_id (see
     * CatalogResourceType::supportsCategoryFacet()), so a category filter
     * narrows to those three instead of to Book alone.
     */
    public function booksOnly(): bool
    {
        return $this->types !== []
            || $this->languages !== []
            || $this->yearFrom !== null
            || $this->yearTo !== null
            || $this->scope === CatalogSearchScope::Isbn;
    }

    /** True when a category facet is in play — narrows to Book/Dissertation/Avtoreferat. */
    public function categoryOnly(): bool
    {
        return $this->categories !== [];
    }
}
