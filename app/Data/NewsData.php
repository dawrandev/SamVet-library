<?php

namespace App\Data;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * DTO for passing data from Controller → Service (news).
 * A typed object instead of an array (`$data['x']`).
 *
 * `body` HTML is passed RAW — the Service sanitizes it with Purifier before saving.
 */
class NewsData
{
    public function __construct(
        /** @var array<string, string> Title (translation: uz/ru/kk) */
        public readonly array $title,
        /** @var array<string, string> Excerpt (translation) */
        public readonly array $excerpt,
        /** @var array<string, string> Rich HTML (translation, raw) */
        public readonly array $body,
        public readonly ?int $news_category_id,
        public readonly ?string $published_at,
        public readonly ?UploadedFile $cover,
        public readonly bool $remove_cover,
        /** @var array<int, UploadedFile> */
        public readonly array $gallery,
        /** @var int[] */
        public readonly array $remove_gallery_ids,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            title: self::localized($request->input('title', [])),
            excerpt: self::localized($request->input('excerpt', [])),
            body: self::localized($request->input('body', []), false),
            news_category_id: $request->integer('news_category_id') ?: null,
            published_at: $request->input('published_at') ?: null,
            cover: $request->file('cover'),
            remove_cover: $request->boolean('remove_cover'),
            gallery: array_values(array_filter(
                (array) $request->file('gallery', []),
                static fn ($file): bool => $file instanceof UploadedFile,
            )),
            remove_gallery_ids: array_values(array_map('intval', $request->input('remove_gallery_ids', []))),
        );
    }

    /**
     * Cleans up the {uz,ru,kk} array — empty languages are dropped.
     *
     * @param  mixed  $value
     * @return array<string, string>
     */
    private static function localized($value, bool $trim = true): array
    {
        return array_filter(
            array_map(static fn ($v): string => $trim ? trim((string) $v) : (string) $v, (array) $value),
            static fn (string $v): bool => trim($v) !== '',
        );
    }

    /**
     * Fields written to the news table (body — raw, sanitized by the Service; without files/gallery).
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'news_category_id' => $this->news_category_id,
            'published_at' => $this->published_at,
        ];
    }
}
