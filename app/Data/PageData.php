<?php

namespace App\Data;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * DTO for passing data from Controller → Service (page).
 *
 * `body` HTML is passed RAW — the Service sanitizes it with Purifier before saving.
 * `title` falls back to the menu item's own title on the public site when empty.
 */
class PageData
{
    public function __construct(
        /** @var array<string, string> Translated plain text */
        public readonly array $title,
        /** @var array<string, string> Rich HTML (translation, raw) */
        public readonly array $body,
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
            title: self::filledStrings((array) $request->input('title', [])),
            body: self::filledStrings((array) $request->input('body', [])),
            cover: $request->file('cover'),
            remove_cover: $request->boolean('remove_cover'),
            gallery: $request->file('gallery', []),
            remove_gallery_ids: array_values(array_map('intval', $request->input('remove_gallery_ids', []))),
        );
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, string>
     */
    private static function filledStrings(array $values): array
    {
        return array_filter(
            array_map(static fn ($v): string => (string) $v, $values),
            static fn (string $v): bool => trim($v) !== '',
        );
    }
}
