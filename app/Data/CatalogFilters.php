<?php

namespace App\Data;

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
     * @param  array<int, int>  $types       selected book type ids
     * @param  array<int, int>  $languages   selected language ids
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
}
