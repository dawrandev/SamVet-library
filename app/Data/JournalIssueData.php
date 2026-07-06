<?php

namespace App\Data;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * DTO for passing data from Controller → Service (journal issue).
 */
class JournalIssueData
{
    public function __construct(
        public readonly int $year,
        public readonly string $issue_number,
        public readonly ?int $pages,
        public readonly ?UploadedFile $cover,
        public readonly ?UploadedFile $electronic_file,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            year: $request->integer('year'),
            issue_number: $request->string('issue_number')->toString(),
            pages: $request->integer('pages') ?: null,
            cover: $request->file('cover'),
            electronic_file: $request->file('electronic_file'),
        );
    }

    /**
     * Only the scalar fields written to the journal_issues table (without files).
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'year' => $this->year,
            'issue_number' => $this->issue_number,
            'pages' => $this->pages,
        ];
    }
}
