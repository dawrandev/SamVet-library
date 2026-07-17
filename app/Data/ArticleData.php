<?php

namespace App\Data;

use App\Enums\ArticleCategory;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * DTO for passing data from Controller → Service (article).
 * A typed object instead of an array (`$data['x']`).
 */
class ArticleData
{
    public function __construct(
        public readonly ?int $journal_issue_id,
        public readonly ?string $external_journal_name,
        public readonly ?int $external_journal_year,
        public readonly string $title,
        public readonly ?string $author,
        public readonly ?int $resource_field_id,
        public readonly ?int $language_id,
        public readonly ?ArticleCategory $category,
        public readonly ?string $doi,
        public readonly ?string $pages,
        public readonly ?string $annotation,
        public readonly ?UploadedFile $electronic_file,
        /** @var array<int, array{contributor_role_id: int, name: string}> */
        public readonly array $contributors = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            journal_issue_id: $request->integer('journal_issue_id') ?: null,
            external_journal_name: $request->input('external_journal_name') ?: null,
            external_journal_year: $request->integer('external_journal_year') ?: null,
            title: $request->string('title')->toString(),
            author: $request->input('author') ?: null,
            resource_field_id: $request->integer('resource_field_id') ?: null,
            language_id: $request->integer('language_id') ?: null,
            category: $request->filled('category') ? ArticleCategory::from($request->string('category')->toString()) : null,
            doi: $request->input('doi') ?: null,
            pages: $request->input('pages') ?: null,
            annotation: $request->input('annotation') ?: null,
            electronic_file: $request->file('electronic_file'),
            contributors: $request->input('contributors', []),
        );
    }

    /**
     * Only the scalar fields written to the articles table (without files).
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'journal_issue_id' => $this->journal_issue_id,
            'external_journal_name' => $this->external_journal_name,
            'external_journal_year' => $this->external_journal_year,
            'title' => $this->title,
            'author' => $this->author,
            'resource_field_id' => $this->resource_field_id,
            'language_id' => $this->language_id,
            'category' => $this->category,
            'doi' => $this->doi,
            'pages' => $this->pages,
            'annotation' => $this->annotation,
        ];
    }
}
