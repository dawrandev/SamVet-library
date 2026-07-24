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
        public readonly ?string $authors,
        /** @var string[] */
        public readonly array $parallel_titles,
        public readonly ?string $udc,
        public readonly ?string $author_mark,
        public readonly ?int $book_type_id,
        public readonly ?int $language_id,
        /** @var int[] full set — $language_id is its first entry, kept for stats/back-compat */
        public readonly array $language_ids,
        public readonly ?string $publisher,
        public readonly ?int $publication_place_id,
        public readonly ?int $publication_year,
        public readonly ?int $pages,
        public readonly ?string $isbn,
        public readonly ?int $print_run,
        public readonly ?string $annotation,
        public readonly ?string $target_audience,
        public readonly ?int $size_cm,
        public readonly ?string $print_sheets,
        /** @var int[] */
        public readonly array $category_ids,
        public readonly ?UploadedFile $cover,
        public readonly ?UploadedFile $electronic_file,
        /** @var array<int, array{contributor_role_id: int, name: string}> */
        public readonly array $contributors = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        // The form submits either "language_id" (single select — the common
        // case) or "language_ids[]" (multiselect — once a parallel title is
        // added). Either way, the FIRST id is what "language_id" ends up
        // being — the one every existing filter/stat already reads.
        $languageIds = $request->input('language_ids');
        if (! is_array($languageIds) || $languageIds === []) {
            $single = $request->integer('language_id') ?: null;
            $languageIds = $single ? [$single] : [];
        } else {
            $languageIds = array_values(array_unique(array_map('intval', $languageIds)));
        }

        $parallelTitles = collect($request->input('parallel_titles', []))
            ->map(fn ($t) => trim((string) $t))
            ->filter()
            ->values()
            ->all();

        return new self(
            title: $request->string('title')->toString(),
            authors: $request->input('authors') ?: null,
            parallel_titles: $parallelTitles,
            udc: $request->input('udc'),
            author_mark: $request->input('author_mark'),
            book_type_id: $request->integer('book_type_id') ?: null,
            language_id: $languageIds[0] ?? null,
            language_ids: $languageIds,
            publisher: $request->input('publisher') ?: null,
            publication_place_id: $request->integer('publication_place_id') ?: null,
            publication_year: $request->integer('publication_year') ?: null,
            pages: $request->integer('pages') ?: null,
            isbn: $request->input('isbn'),
            print_run: $request->integer('print_run') ?: null,
            annotation: $request->input('annotation'),
            target_audience: $request->input('target_audience') ?: null,
            size_cm: $request->integer('size_cm') ?: null,
            print_sheets: $request->input('print_sheets') ?: null,
            category_ids: $request->input('category_ids', []),
            cover: $request->file('cover'),
            electronic_file: $request->file('electronic_file'),
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
            'authors' => $this->authors,
            'parallel_titles' => $this->parallel_titles ?: null,
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
            'target_audience' => $this->target_audience,
            'size_cm' => $this->size_cm,
            'print_sheets' => $this->print_sheets,
        ];
    }
}
