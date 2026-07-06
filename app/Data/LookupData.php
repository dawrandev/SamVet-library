<?php

namespace App\Data;

use Illuminate\Http\Request;

/**
 * DTO for passing data from Controller → Service (lookup management).
 *
 * Two kinds of lookup:
 *  - Translatable (category, book_type, language, location): name = ['uz'=>,'ru'=>,'kk'=>]
 *  - Plain (publisher, author): name = string
 *
 * Category additionally accepts `parent_id`.
 */
class LookupData
{
    /**
     * @param  string|array<string, string>  $name  Array for translatable, string for plain
     */
    public function __construct(
        public readonly string|array $name,
        public readonly ?int $parent_id = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $name = $request->input('name');

        // For a translatable entity the name arrives as an array (name[uz], name[ru], name[kk]).
        if (is_array($name)) {
            $name = [
                'uz' => trim((string) ($name['uz'] ?? '')),
                'ru' => trim((string) ($name['ru'] ?? '')),
                'kk' => trim((string) ($name['kk'] ?? '')),
            ];
        } else {
            $name = trim((string) $name);
        }

        return new self(
            name: $name,
            parent_id: $request->integer('parent_id') ?: null,
        );
    }
}
