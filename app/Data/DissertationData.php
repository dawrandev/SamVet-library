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
        public readonly int $journal_issue_id,
        public readonly string $title,
        public readonly string $author,
        public readonly ?int $resource_field_id,
        public readonly ?string $annotation,
        public readonly ?UploadedFile $electronic_file,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            journal_issue_id: $request->integer('journal_issue_id'),
            title: $request->string('title')->toString(),
            author: $request->string('author')->toString(),
            resource_field_id: $request->integer('resource_field_id') ?: null,
            annotation: $request->input('annotation') ?: null,
            electronic_file: $request->file('electronic_file'),
        );
    }

    /**
     * Only the scalar fields written to the dissertations table (without files).
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'journal_issue_id' => $this->journal_issue_id,
            'title' => $this->title,
            'author' => $this->author,
            'resource_field_id' => $this->resource_field_id,
            'annotation' => $this->annotation,
        ];
    }
}
