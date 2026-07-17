<?php

namespace App\Data;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * DTO for passing data from Controller → Service.
 * A typed object instead of an array (`$data['x']`).
 */
class BookData
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $udc,
        public readonly ?string $author_mark,
        public readonly ?int $book_type_id,
        public readonly ?int $language_id,
        public readonly ?string $publisher,
        public readonly ?int $publication_place_id,
        public readonly ?int $publication_year,
        public readonly ?int $pages,
        public readonly ?string $isbn,
        public readonly ?int $print_run,
        public readonly ?string $annotation,
        /** @var int[] */
        public readonly array $author_ids,
        /** @var int[] */
        public readonly array $category_ids,
        public readonly ?UploadedFile $cover,
        public readonly ?UploadedFile $electronic_file,
        public readonly ?UploadedFile $audio_file,
        /** @var array<int, array{contributor_role_id: int, name: string}> */
        public readonly array $contributors = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            title: $request->string('title')->toString(),
            udc: $request->input('udc'),
            author_mark: $request->input('author_mark'),
            book_type_id: $request->integer('book_type_id') ?: null,
            language_id: $request->integer('language_id') ?: null,
            publisher: $request->input('publisher') ?: null,
            publication_place_id: $request->integer('publication_place_id') ?: null,
            publication_year: $request->integer('publication_year') ?: null,
            pages: $request->integer('pages') ?: null,
            isbn: $request->input('isbn'),
            print_run: $request->integer('print_run') ?: null,
            annotation: $request->input('annotation'),
            author_ids: $request->input('author_ids', []),
            category_ids: $request->input('category_ids', []),
            cover: $request->file('cover'),
            electronic_file: $request->file('electronic_file'),
            audio_file: $request->file('audio_file'),
            contributors: $request->input('contributors', []),
        );
    }

    /**
     * Only the scalar fields written to the books table (without files/relationships).
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'title' => $this->title,
            'udc' => $this->udc,
            'author_mark' => $this->author_mark,
            'book_type_id' => $this->book_type_id,
            'language_id' => $this->language_id,
            'publisher' => $this->publisher,
            'publication_place_id' => $this->publication_place_id,
            'publication_year' => $this->publication_year,
            'pages' => $this->pages,
            'isbn' => $this->isbn,
            'print_run' => $this->print_run,
            'annotation' => $this->annotation,
        ];
    }
}
