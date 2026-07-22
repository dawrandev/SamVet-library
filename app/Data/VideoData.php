<?php

namespace App\Data;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * DTO for passing data from Controller → Service (video).
 */
class VideoData
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $author,
        public readonly ?string $annotation,
        public readonly ?UploadedFile $cover,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            author: $request->input('author') ?: null,
            annotation: $request->input('annotation'),
            cover: $request->file('cover'),
        );
    }

    /**
     * Only the scalar fields written to the videos table (without the file).
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'name' => $this->name,
            'author' => $this->author,
            'annotation' => $this->annotation,
        ];
    }
}
