<?php

namespace App\Data;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * DTO for passing data from Controller → Service (page).
 *
 * `body` HTML is passed RAW — the Service sanitizes it with Purifier before saving.
 * There is no separate title — the menu item's title is used.
 */
class PageData
{
    public function __construct(
        /** @var array<string, string> Rich HTML (translation, raw) */
        public readonly array $body,
        public readonly ?UploadedFile $cover,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $body = array_filter(
            array_map(static fn ($v): string => (string) $v, (array) $request->input('body', [])),
            static fn (string $v): bool => trim($v) !== '',
        );

        return new self(
            body: $body,
            cover: $request->file('cover'),
        );
    }
}
