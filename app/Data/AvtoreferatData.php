<?php

namespace App\Data;

use App\Enums\CopyCondition;
use App\Enums\DissertationDegree;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * DTO for passing data from Controller → Service (avtoreferat).
 * A typed object instead of an array (`$data['x']`).
 */
class AvtoreferatData
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $author,
        public readonly ?string $specialty,
        public readonly ?int $science_field_id,
        public readonly ?DissertationDegree $degree,
        public readonly ?string $council_number,
        public readonly ?string $defense_institution,
        public readonly ?string $performed_institution,
        public readonly string $advisor,
        public readonly ?string $udc,
        public readonly ?string $registration_number,
        public readonly ?CopyCondition $condition,
        public readonly ?int $publication_place_id,
        public readonly ?int $defense_year,
        public readonly ?string $inventory_number,
        public readonly ?int $resource_field_id,
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
            specialty: $request->input('specialty') ?: null,
            science_field_id: $request->integer('science_field_id') ?: null,
            degree: $request->filled('degree') ? DissertationDegree::from($request->string('degree')->toString()) : null,
            council_number: $request->input('council_number') ?: null,
            defense_institution: $request->input('defense_institution') ?: null,
            performed_institution: $request->input('performed_institution') ?: null,
            advisor: $request->string('advisor')->toString(),
            udc: $request->input('udc') ?: null,
            registration_number: $request->input('registration_number') ?: null,
            condition: $request->filled('condition') ? CopyCondition::from($request->string('condition')->toString()) : null,
            publication_place_id: $request->integer('publication_place_id') ?: null,
            defense_year: $request->integer('defense_year') ?: null,
            inventory_number: $request->input('inventory_number') ?: null,
            resource_field_id: $request->integer('resource_field_id') ?: null,
            annotation: $request->input('annotation') ?: null,
            electronic_file: $request->file('electronic_file'),
            contributors: $request->input('contributors', []),
        );
    }

    /**
     * Only the scalar fields written to the avtoreferats table (without files).
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'title' => $this->title,
            'author' => $this->author,
            'specialty' => $this->specialty,
            'science_field_id' => $this->science_field_id,
            'degree' => $this->degree,
            'council_number' => $this->council_number,
            'defense_institution' => $this->defense_institution,
            'performed_institution' => $this->performed_institution,
            'advisor' => $this->advisor,
            'udc' => $this->udc,
            'registration_number' => $this->registration_number,
            'condition' => $this->condition,
            'publication_place_id' => $this->publication_place_id,
            'defense_year' => $this->defense_year,
            'inventory_number' => $this->inventory_number,
            'resource_field_id' => $this->resource_field_id,
            'annotation' => $this->annotation,
        ];
    }
}
