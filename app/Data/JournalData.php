<?php

namespace App\Data;

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
        public readonly ?string $founder,
        public readonly ?int $language_id,
        /** @var array<string, string>|null Publisher (translation: uz/ru/kk) */
        public readonly ?array $publisher,
        public readonly ?int $publication_place_id,
        public readonly ?string $issn,
        public readonly ?string $index,
        public readonly ?string $periodicity,
    ) {}

    public static function fromRequest(Request $request): self
    {
        // Publisher: {uz,ru,kk} — empty values are dropped, null if all are empty
        $publisher = array_filter(
            array_map('trim', (array) $request->input('publisher', [])),
            static fn (string $v): bool => $v !== '',
        );

        return new self(
            name: $request->string('name')->toString(),
            kind: $request->string('kind')->toString(),
            journal_type_id: $request->integer('journal_type_id') ?: null,
            founder: $request->input('founder'),
            language_id: $request->integer('language_id') ?: null,
            publisher: $publisher ?: null,
            publication_place_id: $request->integer('publication_place_id') ?: null,
            issn: $request->input('issn'),
            index: $request->input('index'),
            periodicity: $request->input('periodicity') ?: null,
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
            'founder' => $this->founder,
            'language_id' => $this->language_id,
            'publisher' => $this->publisher,
            'publication_place_id' => $this->publication_place_id,
            'issn' => $this->issn,
            'index' => $this->index,
            'periodicity' => $this->periodicity,
        ];
    }
}
