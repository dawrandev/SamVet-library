<?php

namespace App\Data;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * Controller → Service ma'lumot uzatish uchun DTO.
 * Massiv (`$data['x']`) o'rniga tipli obyekt.
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
        /** @var array<string, string>|null Nashriyot joyi (tarjima: uz/ru/kk) */
        public readonly ?array $publication_place,
        public readonly ?int $pages,
        public readonly ?string $isbn,
        public readonly ?int $print_run,
        public readonly ?string $annotation,
        public readonly bool $has_continuation,
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
        // Nashriyot joyi: {uz,ru,kk} — bo'sh qiymatlar tashlanadi, hammasi bo'sh bo'lsa null
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
            has_continuation: $request->boolean('has_continuation'),
            author_ids: $request->input('author_ids', []),
            category_ids: $request->input('category_ids', []),
            cover: $request->file('cover'),
            electronic_file: $request->file('electronic_file'),
            audio_file: $request->file('audio_file'),
        );
    }

    /**
     * Faqat books jadvaliga yoziladigan skalyar maydonlar (fayl/bog'lanishsiz).
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
            'has_continuation' => $this->has_continuation,
        ];
    }
}
