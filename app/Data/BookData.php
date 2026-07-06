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
        public readonly ?int $publisher_id,
        public readonly ?int $publication_year,
        /** @var array<string, string>|null Publication place (translation: uz/ru/kk) */
        public readonly ?array $publication_place,
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
    ) {}

    public static function fromRequest(Request $request): self
    {
        // Publication place: {uz,ru,kk} — empty values are dropped, null if all are empty
        $place = array_filter(
            array_map('trim', (array) $request->input('publication_place', [])),
            static fn (string $v): bool => $v !== '',
        );

        return new self(
            title: $request->string('title')->toString(),
            udc: $request->input('udc'),
            author_mark: $request->input('author_mark'),
            book_type_id: $request->integer('book_type_id') ?: null,
            language_id: $request->integer('language_id') ?: null,
            publisher_id: $request->integer('publisher_id') ?: null,
            publication_year: $request->integer('publication_year') ?: null,
            publication_place: $place ?: null,
            pages: $request->integer('pages') ?: null,
            isbn: $request->input('isbn'),
            print_run: $request->integer('print_run') ?: null,
            annotation: $request->input('annotation'),
            author_ids: $request->input('author_ids', []),
            category_ids: $request->input('category_ids', []),
            cover: $request->file('cover'),
            electronic_file: $request->file('electronic_file'),
            audio_file: $request->file('audio_file'),
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
            'publisher_id' => $this->publisher_id,
            'publication_year' => $this->publication_year,
            'publication_place' => $this->publication_place,
            'pages' => $this->pages,
            'isbn' => $this->isbn,
            'print_run' => $this->print_run,
            'annotation' => $this->annotation,
        ];
    }
}
