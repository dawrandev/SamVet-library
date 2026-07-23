<?php

namespace App\Data;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * DTO for passing data from Controller → Service (dissertation).
 * A typed object instead of an array (`$data['x']`).
 */
class DissertationData
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $author,
        public readonly ?string $degree,
        public readonly ?int $resource_field_id,
        public readonly ?int $science_field_id,
        public readonly ?int $doctoral_specialty_id,
        public readonly ?int $master_specialty_id,
        public readonly ?string $advisor,
        public readonly ?string $institution,
        public readonly ?int $language_id,
        public readonly ?int $publication_place_id,
        public readonly ?int $defense_year,
        public readonly ?int $pages,
        public readonly ?string $udc,
        public readonly ?string $inventory_number,
        public readonly ?string $condition,
        public readonly ?string $annotation,
        public readonly ?UploadedFile $electronic_file,
        /** @var array<int, array{contributor_role_id: int, name: string}> */
        public readonly array $contributors = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            title: $request->string('title')->toString(),
            author: $request->input('author') ?: null,
            degree: $request->input('degree') ?: null,
            resource_field_id: $request->integer('resource_field_id') ?: null,
            science_field_id: $request->integer('science_field_id') ?: null,
            doctoral_specialty_id: $request->integer('doctoral_specialty_id') ?: null,
            master_specialty_id: $request->integer('master_specialty_id') ?: null,
            advisor: $request->input('advisor') ?: null,
            institution: $request->input('institution') ?: null,
            language_id: $request->integer('language_id') ?: null,
            publication_place_id: $request->integer('publication_place_id') ?: null,
            defense_year: $request->integer('defense_year') ?: null,
            pages: $request->integer('pages') ?: null,
            udc: $request->input('udc') ?: null,
            inventory_number: $request->input('inventory_number') ?: null,
            condition: $request->input('condition') ?: null,
            annotation: $request->input('annotation') ?: null,
            electronic_file: $request->file('electronic_file'),
            contributors: $request->input('contributors', []),
        );
    }

    /**
     * Only the scalar fields written to the dissertations table (without files).
     * The PhD/DSc-vs-Master field split is enforced here: only the fields that
     * apply to the chosen degree are kept, the other side is nulled out.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        $isDoctoral = in_array($this->degree, ['phd', 'dsc'], true);
        $isMaster = $this->degree === 'master';

        return [
            'title' => $this->title,
            'author' => $this->author,
            'degree' => $this->degree,
            'resource_field_id' => $this->resource_field_id,
            'science_field_id' => $isDoctoral ? $this->science_field_id : null,
            'doctoral_specialty_id' => $isDoctoral ? $this->doctoral_specialty_id : null,
            'master_specialty_id' => $isMaster ? $this->master_specialty_id : null,
            'advisor' => $this->advisor,
            'institution' => $this->institution,
            'language_id' => $this->language_id,
            'publication_place_id' => $this->publication_place_id,
            'defense_year' => $this->defense_year,
            'pages' => $this->pages,
            'udc' => $this->udc,
            'inventory_number' => $this->inventory_number,
            'condition' => $this->condition,
            'annotation' => $this->annotation,
        ];
    }
}
