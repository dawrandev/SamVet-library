<?php

namespace App\Data;

use Illuminate\Http\Request;

/**
 * Controller → Service ma'lumot uzatish uchun DTO (lookup boshqaruvi).
 *
 * Ikki xil lookup:
 *  - Tarjimali (category, book_type, language, location): name = ['uz'=>,'ru'=>,'kk'=>]
 *  - Oddiy (publisher, author): name = string
 *
 * Category qo'shimcha `parent_id` qabul qiladi.
 */
class LookupData
{
    /**
     * @param  string|array<string, string>  $name  Tarjimali uchun massiv, oddiy uchun string
     */
    public function __construct(
        public readonly string|array $name,
        public readonly ?int $parent_id = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $name = $request->input('name');

        // Tarjimali entity'da name massiv keladi (name[uz], name[ru], name[kk]).
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
