<?php

namespace App\Data;

use App\Enums\PeriodicityUnit;
use Illuminate\Http\Request;

/**
 * DTO for passing data from Controller → Service (journal).
 * A typed object instead of an array (`$data['x']`).
 */
class JournalData
{
    public function __construct(
        public readonly string $name,
        public readonly string $kind,
        public readonly ?int $journal_type_id,
        public readonly ?string $newspaper_type,
        public readonly ?string $founder,
        public readonly ?int $language_id,
        public readonly ?int $publication_place_id,
        public readonly ?string $issn,
        public readonly ?string $index,
        public readonly ?string $periodicity_unit,
        public readonly ?int $periodicity_interval,
        public readonly ?int $periodicity_count,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $unit = $request->input('periodicity_unit') ?: null;
        $isIrregular = $unit === PeriodicityUnit::Irregular->value;

        return new self(
            name: $request->string('name')->toString(),
            kind: $request->string('kind')->toString(),
            journal_type_id: $request->integer('journal_type_id') ?: null,
            newspaper_type: $request->input('newspaper_type') ?: null,
            founder: $request->input('founder'),
            language_id: $request->integer('language_id') ?: null,
            publication_place_id: $request->integer('publication_place_id') ?: null,
            issn: $request->input('issn'),
            index: $request->input('index'),
            periodicity_unit: $unit,
            periodicity_interval: ($unit && ! $isIrregular) ? ($request->integer('periodicity_interval') ?: 1) : null,
            periodicity_count: ($unit && ! $isIrregular) ? ($request->integer('periodicity_count') ?: 1) : null,
        );
    }

    /**
     * Only the scalar fields written to the journals table.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'name' => $this->name,
            'kind' => $this->kind,
            'journal_type_id' => $this->journal_type_id,
            'newspaper_type' => $this->newspaper_type,
            'founder' => $this->founder,
            'language_id' => $this->language_id,
            'publication_place_id' => $this->publication_place_id,
            'issn' => $this->issn,
            'index' => $this->index,
            'periodicity_unit' => $this->periodicity_unit,
            'periodicity_interval' => $this->periodicity_interval,
            'periodicity_count' => $this->periodicity_count,
        ];
    }
}
