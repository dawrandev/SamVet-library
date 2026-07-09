<?php

namespace App\Http\Requests\Site;

use App\Data\CatalogFilters;
use App\Enums\CatalogSort;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the public catalog query string and maps it to a typed DTO.
 * Public page — always authorized; validation guards against bad query params.
 */
class CatalogFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer'],
            'types' => ['nullable', 'array'],
            'types.*' => ['integer'],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['integer'],
            'year_from' => ['nullable', 'integer', 'min:1000', 'max:2100'],
            'year_to' => ['nullable', 'integer', 'min:1000', 'max:2100'],
            'author' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', Rule::enum(CatalogSort::class)],
        ];
    }

    /** Build the typed filter object from the validated input. */
    public function filters(): CatalogFilters
    {
        $data = $this->validated();

        return new CatalogFilters(
            search: $this->cleanString($data['q'] ?? null),
            categories: array_values(array_map('intval', $data['categories'] ?? [])),
            types: array_values(array_map('intval', $data['types'] ?? [])),
            languages: array_values(array_map('intval', $data['languages'] ?? [])),
            yearFrom: isset($data['year_from']) ? (int) $data['year_from'] : null,
            yearTo: isset($data['year_to']) ? (int) $data['year_to'] : null,
            author: $this->cleanString($data['author'] ?? null),
            sort: isset($data['sort']) ? CatalogSort::from($data['sort']) : CatalogSort::Newest,
        );
    }

    /** Trim a text value and treat an empty string as "not provided". */
    private function cleanString(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
